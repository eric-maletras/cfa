<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\User;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur des sessions pour les formateurs
 * Affiche les sessions sur lesquelles le formateur intervient
 */
#[Route('/module/formateur_sessions')]
#[IsGranted('ROLE_FORMATEUR')]
class FormateurSessionsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionRepository $sessionRepo
    ) {}

    /**
     * Liste des sessions du formateur connecté
     */
    #[Route('', name: 'app_formateur_sessions_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        // Récupère les sessions où le formateur intervient ou est responsable
        $sessions = $this->sessionRepo->findByFormateur($formateur);
        
        // Regrouper par statut pour les statistiques
        $stats = [
            'total' => count($sessions),
            'en_cours' => 0,
            'planifiees' => 0,
            'terminees' => 0,
        ];
        
        foreach ($sessions as $session) {
            match ($session->getStatut()) {
                Session::STATUT_EN_COURS => $stats['en_cours']++,
                Session::STATUT_PLANIFIEE, Session::STATUT_INSCRIPTIONS_OUVERTES => $stats['planifiees']++,
                Session::STATUT_TERMINEE => $stats['terminees']++,
                default => null,
            };
        }
        
        // Séparer les sessions actives des terminées pour l'affichage
        $sessionsActives = array_filter(
            $sessions,
            fn(Session $s) => !in_array($s->getStatut(), [Session::STATUT_TERMINEE, Session::STATUT_ANNULEE])
        );
        
        $sessionsTerminees = array_filter(
            $sessions,
            fn(Session $s) => $s->getStatut() === Session::STATUT_TERMINEE
        );
        
        return $this->render('formateur/sessions/index.html.twig', [
            'sessionsActives' => $sessionsActives,
            'sessionsTerminees' => $sessionsTerminees,
            'stats' => $stats,
            'formateur' => $formateur,
        ]);
    }

    /**
     * Détail d'une session avec liste des apprenants
     */
    #[Route('/{id}', name: 'app_formateur_sessions_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Session $session): Response
    {
        $this->checkAccess($session);
        
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        // Récupérer les inscriptions validées
        $inscriptions = $session->getInscriptionsValidees();
        
        return $this->render('formateur/sessions/show.html.twig', [
            'session' => $session,
            'inscriptions' => $inscriptions,
            'isResponsable' => $session->getResponsable() === $formateur,
        ]);
    }

    /**
     * Vérifie que le formateur a accès à cette session
     */
    private function checkAccess(Session $session): void
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Admin a tous les droits
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Le formateur doit intervenir sur la session ou en être responsable
        if ($session->getFormateurs()->contains($user) || $session->getResponsable() === $user) {
            return;
        }
        
        throw $this->createAccessDeniedException('Vous n\'intervenez pas sur cette session.');
    }
}
