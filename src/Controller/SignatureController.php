<?php

namespace App\Controller;

use App\Repository\PresenceRepository;
use App\Service\AppelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de signature publique
 * 
 * Permet aux apprentis de signer leur présence via un lien unique
 * reçu par email. Aucune authentification n'est requise car le token
 * fait office d'authentification.
 */
#[Route('/signature')]
class SignatureController extends AbstractController
{
    public function __construct(
        private AppelService $appelService,
        private PresenceRepository $presenceRepo
    ) {}

    /**
     * Page de signature avec token
     * 
     * L'apprenti arrive sur cette page via le lien email.
     * Si le token est valide, on affiche une page de confirmation.
     */
    #[Route('/{token}', name: 'app_signature_signer', methods: ['GET', 'POST'])]
    public function signer(Request $request, string $token): Response
    {
        // Rechercher la présence associée au token
        $presence = $this->presenceRepo->findByToken($token);

        if (!$presence) {
            return $this->render('signature/erreur.html.twig', [
                'titre' => 'Lien invalide',
                'message' => 'Ce lien de signature n\'existe pas ou a expiré.',
                'code' => 'TOKEN_INVALIDE',
            ]);
        }

        $appel = $presence->getAppel();
        $seance = $appel->getSeance();
        $apprenti = $presence->getApprenti();

        // Vérifier si la présence peut être signée
        if (!$presence->peutEtreSignee()) {
            // Déterminer la raison et afficher le message approprié
            if ($presence->aSigne()) {
                return $this->render('signature/deja_signe.html.twig', [
                    'presence' => $presence,
                    'seance' => $seance,
                    'apprenti' => $apprenti,
                ]);
            }

            if ($appel->isCloture()) {
                return $this->render('signature/erreur.html.twig', [
                    'titre' => 'Appel clôturé',
                    'message' => 'L\'appel a été clôturé par le formateur. Vous ne pouvez plus signer.',
                    'code' => 'APPEL_CLOTURE',
                ]);
            }

            if (!$appel->isLiensValides()) {
                return $this->render('signature/erreur.html.twig', [
                    'titre' => 'Délai expiré',
                    'message' => sprintf(
                        'Le délai de signature a expiré le %s. Veuillez contacter votre formateur.',
                        $appel->getDateExpiration()->format('d/m/Y à H:i')
                    ),
                    'code' => 'LIEN_EXPIRE',
                ]);
            }

            return $this->render('signature/erreur.html.twig', [
                'titre' => 'Signature impossible',
                'message' => 'Vous ne pouvez pas signer votre présence pour le moment.',
                'code' => 'ERREUR_INCONNUE',
            ]);
        }

        // Si POST, traiter la signature
        if ($request->isMethod('POST')) {
            // Vérification CSRF
            if (!$this->isCsrfTokenValid('signature_' . $token, $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_signature_signer', ['token' => $token]);
            }

            // Récupérer les informations du client
            $ip = $request->getClientIp() ?? 'unknown';
            $userAgent = $request->headers->get('User-Agent', 'unknown');

            // Traiter la signature
            $result = $this->appelService->traiterSignature($token, $ip, $userAgent);

            if ($result['succes']) {
                return $this->render('signature/succes.html.twig', [
                    'presence' => $result['presence'],
                    'seance' => $seance,
                    'apprenti' => $apprenti,
                ]);
            }

            // Erreur lors de la signature
            return $this->render('signature/erreur.html.twig', [
                'titre' => 'Erreur de signature',
                'message' => $result['message'],
                'code' => $result['code'],
            ]);
        }

        // GET : afficher la page de confirmation
        return $this->render('signature/confirmer.html.twig', [
            'presence' => $presence,
            'seance' => $seance,
            'appel' => $appel,
            'apprenti' => $apprenti,
            'token' => $token,
        ]);
    }

    /**
     * Page d'aide/information sur la signature
     */
    #[Route('/aide', name: 'app_signature_aide', methods: ['GET'], priority: 10)]
    public function aide(): Response
    {
        return $this->render('signature/aide.html.twig');
    }
}
