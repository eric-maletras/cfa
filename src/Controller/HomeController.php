<?php
// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Récupérer les statistiques (optionnel)
        $stats = [
            'apprentis' => 450,
            'formations' => 12,
            'entreprises' => 180,
            'taux_reussite' => 92
        ];
        
        return $this->render('home/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
