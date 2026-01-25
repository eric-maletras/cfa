<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Repository\ModuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur du tableau de bord
 * 
 * Affiche les modules accessibles selon les rôles de l'utilisateur connecté.
 * Les modules sont récupérés dynamiquement depuis la base de données.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ModuleRepository $moduleRepository): Response
    {
        $user = $this->getUser();
        
        // Récupérer les modules accessibles pour cet utilisateur
        $modules = $moduleRepository->findAccessibleByUser($user);
        
        return $this->render('dashboard/index.html.twig', [
            'modules' => $modules,
        ]);
    }
}
