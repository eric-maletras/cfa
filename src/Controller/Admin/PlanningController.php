<?php

namespace App\Controller\Admin;

use App\Repository\CalendrierAnneeRepository;
use App\Repository\JourFermeRepository;
use App\Repository\SalleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour le module Planning & Ressources
 * Sous-dashboard regroupant la gestion des salles, calendriers, etc.
 */
#[Route('/admin/planning')]
#[IsGranted('ROLE_ADMIN')]
class PlanningController extends AbstractController
{
    /**
     * Sous-dashboard Planning - Hub d'accès aux ressources
     */
    #[Route('', name: 'admin_planning', methods: ['GET'])]
    public function index(
        SalleRepository $salleRepository,
        CalendrierAnneeRepository $calendrierRepository,
        JourFermeRepository $jourFermeRepository
    ): Response {
        // Statistiques salles
        $statsSalles = [
            'total' => $salleRepository->count([]),
            'actives' => $salleRepository->count(['actif' => true]),
            'parType' => $salleRepository->countByType(),
        ];

        // Statistiques calendrier
        $calendrierActif = $calendrierRepository->findActif();
        $statsCalendrier = [
            'anneeActive' => $calendrierActif?->getCode() ?? 'Aucune',
            'nbJoursFermes' => $calendrierActif ? $calendrierActif->getNbJoursFermes() : 0,
            'prochainsFermes' => $calendrierActif 
                ? $jourFermeRepository->findUpcoming($calendrierActif, 3)
                : [],
        ];

        $stats = [
            'salles' => $statsSalles,
            'calendrier' => $statsCalendrier,
        ];

        // Définition des sous-modules du planning
        $sousModules = [
            [
                'nom' => 'Gestion des salles',
                'description' => 'Créer et gérer les salles de cours, laboratoires et amphithéâtres',
                'icone' => 'door-open',
                'route' => 'admin_salle_index',
                'couleur' => 'primary',
                'stats' => $stats['salles']['actives'] . ' salle(s) active(s)',
            ],
            [
                'nom' => 'Calendrier annuel',
                'description' => 'Gérer les années scolaires et les jours fermés (fériés, vacances, ponts)',
                'icone' => 'calendar',
                'route' => 'admin_calendrier_index',
                'couleur' => 'secondary',
                'stats' => $calendrierActif 
                    ? sprintf('%s - %d jour(s) fermé(s)', $statsCalendrier['anneeActive'], $statsCalendrier['nbJoursFermes'])
                    : 'Aucun calendrier actif',
            ],
            [
                'nom' => 'Créneaux horaires',
                'description' => 'Configurer les plages horaires de cours',
                'icone' => 'clock',
                'route' => null, // À implémenter
                'couleur' => 'secondary',
                'stats' => 'Prochainement',
                'disabled' => true,
            ],
            [
                'nom' => 'Réservations',
                'description' => 'Gérer les réservations de salles',
                'icone' => 'bookmark',
                'route' => null, // À implémenter
                'couleur' => 'tertiary',
                'stats' => 'Prochainement',
                'disabled' => true,
            ],
        ];

        return $this->render('admin/planning/index.html.twig', [
            'sousModules' => $sousModules,
            'stats' => $stats,
            'calendrierActif' => $calendrierActif,
        ]);
    }
}
