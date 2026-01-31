<?php

namespace App\Controller;

use App\Entity\Appel;
use App\Entity\Presence;
use App\Entity\SeancePlanifiee;
use App\Entity\User;
use App\Enum\StatutPresence;
use App\Repository\AppelRepository;
use App\Repository\PresenceRepository;
use App\Service\AppelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des appels pour les formateurs
 * 
 * IMPORTANT: L'appel ne peut être créé que PENDANT le créneau du cours.
 * Le formateur ne peut PAS valider manuellement une présence
 * (exigence OPCO/légale : seule la signature électronique fait foi)
 */
#[Route('/module/formateur_planning/appel')]
#[IsGranted('ROLE_FORMATEUR')]
class AppelController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AppelService $appelService,
        private AppelRepository $appelRepo,
        private PresenceRepository $presenceRepo
    ) {}

    /**
     * Page de création/gestion de l'appel pour une séance
     */
    #[Route('/seance/{id}', name: 'app_appel_seance', methods: ['GET'])]
    public function appelSeance(SeancePlanifiee $seance): Response
    {
        $this->checkAccess($seance);

        // Vérifier si la séance est en cours
        $seanceEnCours = $this->isSeanceEnCours($seance);

        // Vérifier s'il existe déjà un appel (actif ou clôturé)
        $appelsExistants = $this->appelRepo->findBySeance($seance);
        
        // S'il y a au moins un appel, rediriger vers le plus récent
        if (count($appelsExistants) > 0) {
            $dernierAppel = $appelsExistants[0];
            return $this->redirectToRoute('app_appel_suivi', ['id' => $dernierAppel->getId()]);
        }

        /** @var User $formateur */
        $formateur = $this->getUser();

        // Récupérer les apprentis de la session
        $apprentis = [];
        $session = $seance->getSession();
        if ($session) {
            $inscriptions = $session->getInscriptionsValidees();
            foreach ($inscriptions as $inscription) {
                $apprentis[] = $inscription->getUser();
            }
            usort($apprentis, fn(User $a, User $b) => strcmp($a->getNom(), $b->getNom()));
        }

        return $this->render('appel/seance.html.twig', [
            'seance' => $seance,
            'appelActif' => null,
            'apprentis' => $apprentis,
            'historiqueAppels' => [],
            'formateur' => $formateur,
            'seanceEnCours' => $seanceEnCours,
        ]);
    }

    /**
     * Crée un nouvel appel
     * UNIQUEMENT si la séance est en cours
     */
    #[Route('/creer/{id}', name: 'app_appel_creer', methods: ['POST'])]
    public function creerAppel(Request $request, SeancePlanifiee $seance): Response
    {
        $this->checkAccess($seance);

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('appel_creer_' . $seance->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_seance', ['id' => $seance->getId()]);
        }

        // RESTRICTION TEMPORELLE : vérifier que la séance est en cours
        if (!$this->isSeanceEnCours($seance)) {
            $this->addFlash('error', sprintf(
                'L\'appel ne peut être fait que pendant le cours (de %s à %s).',
                $seance->getHeureDebut()->format('H:i'),
                $seance->getHeureFin()->format('H:i')
            ));
            return $this->redirectToRoute('app_appel_seance', ['id' => $seance->getId()]);
        }

        /** @var User $formateur */
        $formateur = $this->getUser();

        // Récupérer les apprentis sélectionnés comme présents
        $apprentisPresents = $request->request->all('apprentis_presents') ?? [];
        $apprentisPresents = array_map('intval', $apprentisPresents);

        // Récupérer le délai d'expiration EN MINUTES (15, 20, 40)
        $expirationMinutes = $request->request->getInt('expiration_minutes', 20);
        
        $valeursAutorisees = [15, 20, 40];
        if (!in_array($expirationMinutes, $valeursAutorisees)) {
            $expirationMinutes = 20;
        }

        try {
            $appel = $this->appelService->creerAppel(
                $seance,
                $formateur,
                $apprentisPresents,
                $expirationMinutes
            );

            $this->addFlash('success', sprintf(
                'Appel créé avec succès. %d apprenti(s) en attente de signature, %d absent(s). Expiration dans %d minutes.',
                $appel->getNbEnAttente(),
                $appel->getNbAbsents(),
                $expirationMinutes
            ));

            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création de l\'appel : ' . $e->getMessage());
            return $this->redirectToRoute('app_appel_seance', ['id' => $seance->getId()]);
        }
    }

    /**
     * Page de suivi d'un appel (état des signatures)
     */
    #[Route('/suivi/{id}', name: 'app_appel_suivi', methods: ['GET'])]
    public function suiviAppel(Appel $appel): Response
    {
        $this->checkAccessAppel($appel);

        $stats = $this->appelService->getStatistiquesAppel($appel);
        $presences = $this->presenceRepo->findByAppel($appel);
        $seance = $appel->getSeance();

        return $this->render('appel/suivi.html.twig', [
            'appel' => $appel,
            'presences' => $presences,
            'stats' => $stats,
            'seance' => $seance,
            'seanceEnCours' => $this->isSeanceEnCours($seance),
        ]);
    }

    /**
     * Envoie les emails de signature
     */
    #[Route('/envoyer-emails/{id}', name: 'app_appel_envoyer_emails', methods: ['POST'])]
    public function envoyerEmails(Request $request, Appel $appel): Response
    {
        $this->checkAccessAppel($appel);

        if (!$this->isCsrfTokenValid('appel_emails_' . $appel->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        try {
            $resultats = $this->appelService->envoyerEmails($appel);

            if ($resultats['echecs'] > 0) {
                $this->addFlash('warning', sprintf(
                    'Emails envoyés : %d succès, %d échec(s).',
                    $resultats['succes'],
                    $resultats['echecs']
                ));
            } else {
                $this->addFlash('success', sprintf(
                    '%d email(s) de signature envoyé(s) avec succès.',
                    $resultats['succes']
                ));
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi des emails : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
    }

    /**
     * Renvoie un email individuel
     */
    #[Route('/renvoyer-email/{id}', name: 'app_appel_renvoyer_email', methods: ['POST'])]
    public function renvoyerEmail(Request $request, Presence $presence): Response
    {
        $appel = $presence->getAppel();
        $this->checkAccessAppel($appel);

        if (!$this->isCsrfTokenValid('renvoyer_email_' . $presence->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        $success = $this->appelService->renvoyerEmail($presence);

        if ($success) {
            $this->addFlash('success', sprintf(
                'Email renvoyé à %s.',
                $presence->getApprenti()->getNomComplet()
            ));
        } else {
            $this->addFlash('error', 'Impossible de renvoyer l\'email.');
        }

        return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
    }

    /**
     * Rouvre un appel clôturé pour permettre aux retardataires de signer
     * UNIQUEMENT si la séance est toujours en cours
     */
    #[Route('/rouvrir/{id}', name: 'app_appel_rouvrir', methods: ['POST'])]
    public function rouvrirAppel(Request $request, Appel $appel): Response
    {
        $this->checkAccessAppel($appel);

        if (!$this->isCsrfTokenValid('appel_rouvrir_' . $appel->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        // RESTRICTION TEMPORELLE
        if (!$this->isSeanceEnCours($appel->getSeance())) {
            $this->addFlash('error', 'Impossible de rouvrir l\'appel : le cours est terminé.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        // Récupérer les retardataires sélectionnés
        $retardataires = $request->request->all('retardataires') ?? [];
        $retardataires = array_map('intval', $retardataires);

        if (empty($retardataires)) {
            $this->addFlash('warning', 'Veuillez sélectionner au moins un retardataire.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        $expirationMinutes = $request->request->getInt('expiration_minutes', 15);
        $valeursAutorisees = [15, 20, 40];
        if (!in_array($expirationMinutes, $valeursAutorisees)) {
            $expirationMinutes = 15;
        }

        try {
            $resultats = $this->appelService->rouvrirAppel($appel, $retardataires, $expirationMinutes);

            $this->addFlash('success', sprintf(
                'Appel rouvert. %d retardataire(s) notifié(s). Retard : %d minutes. Expiration dans %d minutes.',
                $resultats['nbRetardataires'],
                $resultats['minutesRetard'],
                $expirationMinutes
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la réouverture : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
    }

    /**
     * Clôture l'appel
     */
    #[Route('/cloturer/{id}', name: 'app_appel_cloturer', methods: ['POST'])]
    public function cloturerAppel(Request $request, Appel $appel): Response
    {
        $this->checkAccessAppel($appel);

        if (!$this->isCsrfTokenValid('appel_cloturer_' . $appel->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        try {
            $stats = $this->appelService->cloturerAppel($appel);

            $this->addFlash('success', sprintf(
                'Appel clôturé. Taux de présence : %.1f%% (%d présent(s), %d absent(s), %d non signé(s)).',
                $stats['tauxPresence'],
                $stats['presents'],
                $stats['absents'],
                $stats['nonSignes']
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la clôture : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
    }

    /**
     * Récupère l'état actuel des présences (AJAX pour refresh temps réel)
     */
    #[Route('/etat/{id}', name: 'app_appel_etat', methods: ['GET'])]
    public function etatAppel(Appel $appel): JsonResponse
    {
        $this->checkAccessAppel($appel);

        $stats = $this->appelService->getStatistiquesAppel($appel);
        $presences = $this->presenceRepo->findByAppel($appel);

        $presencesData = [];
        foreach ($presences as $presence) {
            $presencesData[] = [
                'id' => $presence->getId(),
                'apprenti' => [
                    'id' => $presence->getApprenti()->getId(),
                    'nom' => $presence->getApprenti()->getNom(),
                    'prenom' => $presence->getApprenti()->getPrenom(),
                ],
                'statut' => $presence->getStatut()->value,
                'statutLibelle' => $presence->getStatut()->getLibelle(),
                'statutIcone' => $presence->getStatut()->getIcone(),
                'statutBadge' => $presence->getStatut()->getBadgeClass(),
                'dateSignature' => $presence->getDateSignature()?->format('H:i:s'),
                'emailEnvoye' => $presence->isEmailEnvoye(),
                'minutesRetard' => $presence->getMinutesRetard(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'stats' => $stats,
            'presences' => $presencesData,
            'cloture' => $appel->isCloture(),
            'liensValides' => $appel->isLiensValides(),
            'seanceEnCours' => $this->isSeanceEnCours($appel->getSeance()),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Supprime un appel non clôturé
     */
    #[Route('/supprimer/{id}', name: 'app_appel_supprimer', methods: ['POST'])]
    public function supprimerAppel(Request $request, Appel $appel): Response
    {
        $this->checkAccessAppel($appel);

        if (!$this->isCsrfTokenValid('appel_supprimer_' . $appel->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_seance', ['id' => $appel->getSeance()->getId()]);
        }

        if ($appel->isCloture()) {
            $this->addFlash('error', 'Impossible de supprimer un appel clôturé.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        $seanceId = $appel->getSeance()->getId();
        $this->appelRepo->remove($appel, true);

        $this->addFlash('success', 'Appel supprimé.');
        return $this->redirectToRoute('app_formateur_planning_seance', ['id' => $seanceId]);
    }

    /**
     * Justifie une absence (seule modification autorisée par le formateur)
     */
    #[Route('/justifier-absence/{id}', name: 'app_appel_justifier_absence', methods: ['POST'])]
    public function justifierAbsence(Request $request, Presence $presence): Response
    {
        $appel = $presence->getAppel();
        $this->checkAccessAppel($appel);

        if (!$this->isCsrfTokenValid('justifier_absence_' . $presence->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        $statutsJustifiables = [StatutPresence::ABSENT, StatutPresence::NON_SIGNE];
        if (!in_array($presence->getStatut(), $statutsJustifiables)) {
            $this->addFlash('error', 'Seule une absence peut être justifiée.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        $motif = trim($request->request->get('motif', ''));
        if (empty($motif)) {
            $this->addFlash('error', 'Un motif est obligatoire pour justifier une absence.');
            return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
        }

        try {
            $presence->justifierAbsence($motif);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Absence de %s justifiée.',
                $presence->getApprenti()->getNomComplet()
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_appel_suivi', ['id' => $appel->getId()]);
    }

    /**
     * Vérifie si la séance est actuellement en cours
     * 
     * Compare l'heure actuelle avec les heures de début et fin de la séance
     * en tenant compte de la date de la séance
     */
    private function isSeanceEnCours(SeancePlanifiee $seance): bool
    {
        $now = new \DateTime();
        $today = new \DateTime('today');
        $dateSeance = $seance->getDate();

        // Vérifier que c'est le bon jour
        if ($dateSeance->format('Y-m-d') !== $today->format('Y-m-d')) {
            return false;
        }

        // Construire les DateTime complets avec la date du jour
        $heureDebut = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d') . ' ' . $seance->getHeureDebut()->format('H:i:s')
        );
        $heureFin = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d') . ' ' . $seance->getHeureFin()->format('H:i:s')
        );

        return $now >= $heureDebut && $now <= $heureFin;
    }

    /**
     * Vérifie l'accès du formateur à une séance
     */
    private function checkAccess(SeancePlanifiee $seance): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($seance->getFormateurs()->contains($user)) {
            return;
        }

        $session = $seance->getSession();
        if ($session && $session->getFormateurs()->contains($user)) {
            return;
        }

        throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette séance.');
    }

    /**
     * Vérifie l'accès du formateur à un appel
     */
    private function checkAccessAppel(Appel $appel): void
    {
        $this->checkAccess($appel->getSeance());
    }
}
