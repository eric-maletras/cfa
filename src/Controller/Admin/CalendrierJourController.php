<?php

namespace App\Controller\Admin;

use App\Entity\CalendrierAnnee;
use App\Entity\SeancePlanifiee;
use App\Enum\StatutSeance;
use App\Form\SeancePlanifieeType;
use App\Repository\SeancePlanifieeRepository;
use App\Repository\JourFermeRepository;
use App\Service\ConflitPlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la vue jour du calendrier et gestion des séances
 */
#[Route('/admin/calendriers')]
#[IsGranted('ROLE_ADMIN')]
class CalendrierJourController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SeancePlanifieeRepository $seanceRepository,
        private JourFermeRepository $jourFermeRepository,
        private ConflitPlanningService $conflitService,
    ) {
    }

    /**
     * Vue détaillée d'une journée du calendrier
     * Affiche toutes les séances planifiées pour cette date
     */
    #[Route('/{id}/jour/{date}', name: 'admin_calendrier_jour', methods: ['GET'])]
    public function jour(CalendrierAnnee $calendrier, string $date): Response
    {
        // Parser la date
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Date invalide.');
        }

        // Vérifier que la date est dans la plage du calendrier
        if ($dateObj < $calendrier->getDateDebut() || $dateObj > $calendrier->getDateFin()) {
            $this->addFlash('warning', 'Cette date est en dehors de la période du calendrier.');
        }

        // Récupérer les séances de cette journée
        $seances = $this->seanceRepository->findByDate($dateObj);

        // Vérifier si c'est un jour fermé
        $jourFerme = $this->jourFermeRepository->findOneBy([
            'calendrier' => $calendrier,
            'date' => $dateObj,
        ]);

        // Calculer le jour précédent et suivant (pour navigation)
        $jourPrecedent = (clone $dateObj)->modify('-1 day');
        $jourSuivant = (clone $dateObj)->modify('+1 day');

        // Limiter la navigation aux bornes du calendrier
        if ($jourPrecedent < $calendrier->getDateDebut()) {
            $jourPrecedent = null;
        }
        if ($jourSuivant > $calendrier->getDateFin()) {
            $jourSuivant = null;
        }

        // Grouper les séances par créneau horaire pour l'affichage
        $seancesParHeure = [];
        foreach ($seances as $seance) {
            $heure = $seance->getHeureDebut()->format('H:i');
            if (!isset($seancesParHeure[$heure])) {
                $seancesParHeure[$heure] = [];
            }
            $seancesParHeure[$heure][] = $seance;
        }
        ksort($seancesParHeure);

        return $this->render('admin/calendrier/jour.html.twig', [
            'calendrier' => $calendrier,
            'date' => $dateObj,
            'dateStr' => $date,
            'seances' => $seances,
            'seancesParHeure' => $seancesParHeure,
            'jourFerme' => $jourFerme,
            'jourPrecedent' => $jourPrecedent,
            'jourSuivant' => $jourSuivant,
            'jourSemaine' => $this->getJourSemaineFr($dateObj),
            'statuts' => StatutSeance::cases(),
        ]);
    }

    /**
     * Ajouter une séance manuelle sur un jour
     */
    #[Route('/{id}/jour/{date}/new', name: 'admin_calendrier_jour_new', methods: ['GET', 'POST'])]
    public function new(CalendrierAnnee $calendrier, string $date, Request $request): Response
    {
        // Parser la date
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Date invalide.');
        }

        // Créer une nouvelle séance avec la date pré-remplie
        $seance = new SeancePlanifiee();
        $seance->setDate($dateObj);
        $seance->setHeureDebut(new \DateTime('08:00:00'));
        $seance->setHeureFin(new \DateTime('10:00:00'));
        $seance->setStatut(StatutSeance::PLANIFIEE);

        $form = $this->createForm(SeancePlanifieeType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les conflits
            $conflits = $this->detecterConflits($seance);
            
            if (!empty($conflits) && !$request->request->get('ignorer_conflits')) {
                foreach ($conflits as $conflit) {
                    $this->addFlash('warning', $conflit);
                }
                // Ajouter option pour ignorer les conflits
                return $this->render('admin/calendrier/jour_new.html.twig', [
                    'calendrier' => $calendrier,
                    'date' => $dateObj,
                    'dateStr' => $date,
                    'form' => $form,
                    'conflits' => $conflits,
                    'jourSemaine' => $this->getJourSemaineFr($dateObj),
                ]);
            }

            $this->entityManager->persist($seance);
            $this->entityManager->flush();

            $this->addFlash('success', 'Séance ajoutée avec succès.');

            return $this->redirectToRoute('admin_calendrier_jour', [
                'id' => $calendrier->getId(),
                'date' => $date,
            ]);
        }

        return $this->render('admin/calendrier/jour_new.html.twig', [
            'calendrier' => $calendrier,
            'date' => $dateObj,
            'dateStr' => $date,
            'form' => $form,
            'conflits' => [],
            'jourSemaine' => $this->getJourSemaineFr($dateObj),
        ]);
    }

    /**
     * Modifier une séance existante
     */
    #[Route('/{id}/jour/{date}/seance/{seanceId}/edit', name: 'admin_calendrier_jour_edit', methods: ['GET', 'POST'])]
    public function edit(
        CalendrierAnnee $calendrier, 
        string $date, 
        int $seanceId,
        Request $request
    ): Response {
        // Parser la date
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Date invalide.');
        }

        // Récupérer la séance
        $seance = $this->seanceRepository->find($seanceId);
        if (!$seance) {
            throw $this->createNotFoundException('Séance non trouvée.');
        }

        $form = $this->createForm(SeancePlanifieeType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Marquer comme modifiée manuellement
            $seance->setModifieeDepuisCreneau(true);

            // Vérifier les conflits
            $conflits = $this->detecterConflits($seance);
            
            if (!empty($conflits) && !$request->request->get('ignorer_conflits')) {
                foreach ($conflits as $conflit) {
                    $this->addFlash('warning', $conflit);
                }
                return $this->render('admin/calendrier/jour_edit.html.twig', [
                    'calendrier' => $calendrier,
                    'date' => $dateObj,
                    'dateStr' => $date,
                    'seance' => $seance,
                    'form' => $form,
                    'conflits' => $conflits,
                    'jourSemaine' => $this->getJourSemaineFr($dateObj),
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Séance modifiée avec succès.');

            return $this->redirectToRoute('admin_calendrier_jour', [
                'id' => $calendrier->getId(),
                'date' => $date,
            ]);
        }

        return $this->render('admin/calendrier/jour_edit.html.twig', [
            'calendrier' => $calendrier,
            'date' => $dateObj,
            'dateStr' => $date,
            'seance' => $seance,
            'form' => $form,
            'conflits' => [],
            'jourSemaine' => $this->getJourSemaineFr($dateObj),
        ]);
    }

    /**
     * Changer rapidement le statut d'une séance
     */
    #[Route('/{id}/jour/{date}/seance/{seanceId}/statut', name: 'admin_calendrier_jour_statut', methods: ['POST'])]
    public function changeStatut(
        CalendrierAnnee $calendrier,
        string $date,
        int $seanceId,
        Request $request
    ): Response {
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('change_statut_' . $seanceId, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_calendrier_jour', [
                'id' => $calendrier->getId(),
                'date' => $date,
            ]);
        }

        $seance = $this->seanceRepository->find($seanceId);
        if (!$seance) {
            throw $this->createNotFoundException('Séance non trouvée.');
        }

        $nouveauStatut = $request->request->get('statut');
        $statutEnum = StatutSeance::tryFrom($nouveauStatut);

        if (!$statutEnum) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_calendrier_jour', [
                'id' => $calendrier->getId(),
                'date' => $date,
            ]);
        }

        // Vérifier si la transition est possible
        if (!$seance->getStatut()->peutTransitionnerVers($statutEnum)) {
            $this->addFlash('error', sprintf(
                'Impossible de passer du statut "%s" à "%s".',
                $seance->getStatut()->getLibelle(),
                $statutEnum->getLibelle()
            ));
            return $this->redirectToRoute('admin_calendrier_jour', [
                'id' => $calendrier->getId(),
                'date' => $date,
            ]);
        }

        // Récupérer le commentaire si annulation
        $commentaire = $request->request->get('commentaire');
        if ($statutEnum === StatutSeance::ANNULEE && $commentaire) {
            $seance->setCommentaire($commentaire);
        }

        $seance->setStatut($statutEnum);
        $seance->setModifieeDepuisCreneau(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Séance %s.',
            strtolower($statutEnum->getLibelle())
        ));

        return $this->redirectToRoute('admin_calendrier_jour', [
            'id' => $calendrier->getId(),
            'date' => $date,
        ]);
    }

    /**
     * Supprimer une séance
     */
    #[Route('/{id}/jour/{date}/seance/{seanceId}/delete', name: 'admin_calendrier_jour_delete', methods: ['POST'])]
    public function delete(
        CalendrierAnnee $calendrier,
        string $date,
        int $seanceId,
        Request $request
    ): Response {
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('delete_seance_' . $seanceId, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_calendrier_jour', [
                'id' => $calendrier->getId(),
                'date' => $date,
            ]);
        }

        $seance = $this->seanceRepository->find($seanceId);
        if (!$seance) {
            throw $this->createNotFoundException('Séance non trouvée.');
        }

        $this->entityManager->remove($seance);
        $this->entityManager->flush();

        $this->addFlash('success', 'Séance supprimée.');

        return $this->redirectToRoute('admin_calendrier_jour', [
            'id' => $calendrier->getId(),
            'date' => $date,
        ]);
    }

    /**
     * Détecte les conflits pour une séance
     * 
     * @return string[] Messages de conflit
     */
    private function detecterConflits(SeancePlanifiee $seance): array
    {
        $conflits = [];

        // Conflit de salle
        $conflitSalle = $this->conflitService->detecterConflitSalle(
            $seance->getSalle(),
            $seance->getDate(),
            $seance->getHeureDebut(),
            $seance->getHeureFin(),
            $seance->getId()
        );

        if ($conflitSalle) {
            $conflits[] = sprintf(
                'Conflit de salle : %s est déjà réservée de %s à %s pour "%s".',
                $seance->getSalle()->getLibelle(),
                $conflitSalle->getHeureDebut()->format('H:i'),
                $conflitSalle->getHeureFin()->format('H:i'),
                $conflitSalle->getSessionMatiere()?->getMatiere()?->getLibelle() ?? 'une autre séance'
            );
        }

        // Conflit de formateurs
        foreach ($seance->getFormateurs() as $formateur) {
            $conflitFormateur = $this->conflitService->detecterConflitFormateur(
                $formateur,
                $seance->getDate(),
                $seance->getHeureDebut(),
                $seance->getHeureFin(),
                $seance->getId()
            );

            if ($conflitFormateur) {
                $conflits[] = sprintf(
                    'Conflit formateur : %s a déjà cours de %s à %s.',
                    $formateur->getNomComplet(),
                    $conflitFormateur->getHeureDebut()->format('H:i'),
                    $conflitFormateur->getHeureFin()->format('H:i')
                );
            }
        }

        return $conflits;
    }

    /**
     * Retourne le nom du jour en français
     */
    private function getJourSemaineFr(\DateTime $date): string
    {
        $jours = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche',
        ];

        return $jours[$date->format('l')] ?? $date->format('l');
    }
}
