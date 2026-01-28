<?php

namespace App\Controller\Admin;

use App\Entity\CalendrierAnnee;
use App\Repository\SeancePlanifieeRepository;
use App\Repository\JourFermeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/calendriers')]
#[IsGranted('ROLE_ADMIN')]
class CalendrierJourController extends AbstractController
{
    public function __construct(
        private SeancePlanifieeRepository $seanceRepository,
        private JourFermeRepository $jourFermeRepository,
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
            'seances' => $seances,
            'seancesParHeure' => $seancesParHeure,
            'jourFerme' => $jourFerme,
            'jourPrecedent' => $jourPrecedent,
            'jourSuivant' => $jourSuivant,
            'jourSemaine' => $this->getJourSemaineFr($dateObj),
        ]);
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
