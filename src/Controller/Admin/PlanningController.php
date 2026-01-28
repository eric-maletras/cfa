<?php

namespace App\Controller\Admin;

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
    public function index(SalleRepository $salleRepository): Response
    {
        // Statistiques pour les cartes
        $stats = [
            'salles' => [
                'total' => $salleRepository->count([]),
                'actives' => $salleRepository->count(['actif' => true]),
                'parType' => $salleRepository->countByType(),
            ],
            // Futures statistiques pour calendrier, créneaux, etc.
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
                'nom' => 'Calendrier',
                'description' => 'Visualiser et gérer le calendrier des sessions',
                'icone' => 'calendar',
                'route' => null, // À implémenter
                'couleur' => 'secondary',
                'stats' => 'Prochainement',
                'disabled' => true,
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
        ]);
    }
}
