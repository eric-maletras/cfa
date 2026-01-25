<?php

namespace App\Controller;

use App\Entity\Session;
use App\Form\SessionType;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des sessions de formation
 */
#[Route('/session')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SessionController extends AbstractController
{
    // ========================================
    // LISTE
    // ========================================

    /**
     * Liste toutes les sessions avec filtres
     */
    #[Route('', name: 'app_session_index', methods: ['GET'])]
    public function index(Request $request, SessionRepository $sessionRepository): Response
    {
        // Récupérer les filtres
        $statut = $request->query->get('statut');
        $annee = $request->query->get('annee');
        $search = $request->query->get('q');
        
        // Appliquer les filtres
        if (!empty($search)) {
            $sessions = $sessionRepository->search($search);
        } elseif (!empty($statut)) {
            $sessions = $sessionRepository->findByStatut($statut);
        } elseif (!empty($annee)) {
            $sessions = $sessionRepository->findByAnnee((int) $annee);
        } else {
            $sessions = $sessionRepository->findActiveWithFormation();
        }
        
        // Stats pour les badges
        $stats = $sessionRepository->countByStatut();
        $annees = $sessionRepository->findDistinctAnnees();
        
        return $this->render('session/index.html.twig', [
            'sessions' => $sessions,
            'stats' => $stats,
            'annees' => $annees,
            'current_statut' => $statut,
            'current_annee' => $annee,
            'search_term' => $search,
            'statuts' => Session::STATUTS,
        ]);
    }

    // ========================================
    // CRÉATION
    // ========================================

    /**
     * Création d'une nouvelle session
     */
    #[Route('/new', name: 'app_session_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em, SessionRepository $sessionRepository): Response
    {
        $session = new Session();
        
        // Pré-remplir les dates par défaut (rentrée septembre)
        $annee = (int) date('Y');
        $mois = (int) date('m');
        
        // Si on est après septembre, proposer l'année suivante
        if ($mois >= 9) {
            $annee++;
        }
        
        $session->setDateDebut(new \DateTime("$annee-09-01"));
        $session->setDateFin(new \DateTime(($annee + 2) . "-07-31"));
        
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code
            if (!$sessionRepository->isCodeUnique($session->getCode())) {
                $this->addFlash('error', 'Ce code de session existe déjà.');
                return $this->render('session/new.html.twig', [
                    'form' => $form,
                    'session' => $session,
                ]);
            }
            
            $em->persist($session);
            $em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été créée avec succès.',
                $session->getLibelle()
            ));
            
            return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
        }
        
        return $this->render('session/new.html.twig', [
            'form' => $form,
            'session' => $session,
        ]);
    }

    // ========================================
    // DÉTAIL
    // ========================================

    /**
     * Affiche le détail d'une session
     */
    #[Route('/{id}', name: 'app_session_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Session $session): Response
    {
        return $this->render('session/show.html.twig', [
            'session' => $session,
        ]);
    }

    // ========================================
    // MODIFICATION
    // ========================================

    /**
     * Modification d'une session existante
     */
    #[Route('/{id}/edit', name: 'app_session_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Session $session, EntityManagerInterface $em, SessionRepository $sessionRepository): Response
    {
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code (en excluant la session actuelle)
            if (!$sessionRepository->isCodeUnique($session->getCode(), $session->getId())) {
                $this->addFlash('error', 'Ce code de session existe déjà.');
                return $this->render('session/edit.html.twig', [
                    'form' => $form,
                    'session' => $session,
                ]);
            }
            
            $em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été modifiée avec succès.',
                $session->getLibelle()
            ));
            
            return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
        }
        
        return $this->render('session/edit.html.twig', [
            'form' => $form,
            'session' => $session,
        ]);
    }

    // ========================================
    // SUPPRESSION
    // ========================================

    /**
     * Suppression d'une session (soft delete = désactivation)
     */
    #[Route('/{id}/delete', name: 'app_session_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Session $session, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $session->getId(), $request->request->get('_token'))) {
            // Soft delete : on désactive plutôt que supprimer
            $session->setActif(false);
            $session->setStatut(Session::STATUT_ANNULEE);
            $em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été désactivée.',
                $session->getLibelle()
            ));
        }
        
        return $this->redirectToRoute('app_session_index');
    }

    // ========================================
    // ACTIONS RAPIDES (Changement de statut)
    // ========================================

    /**
     * Ouvre les inscriptions pour une session
     */
    #[Route('/{id}/ouvrir-inscriptions', name: 'app_session_ouvrir_inscriptions', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function ouvrirInscriptions(Request $request, Session $session, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('ouvrir' . $session->getId(), $request->request->get('_token'))) {
            if ($session->getStatut() === Session::STATUT_PLANIFIEE) {
                $session->setStatut(Session::STATUT_INSCRIPTIONS_OUVERTES);
                $em->flush();
                
                $this->addFlash('success', 'Les inscriptions sont maintenant ouvertes.');
            } else {
                $this->addFlash('warning', 'Impossible d\'ouvrir les inscriptions pour cette session.');
            }
        }
        
        return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
    }

    /**
     * Démarre une session (passe en "en cours")
     */
    #[Route('/{id}/demarrer', name: 'app_session_demarrer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function demarrer(Request $request, Session $session, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('demarrer' . $session->getId(), $request->request->get('_token'))) {
            $statutsValides = [Session::STATUT_PLANIFIEE, Session::STATUT_INSCRIPTIONS_OUVERTES];
            
            if (in_array($session->getStatut(), $statutsValides)) {
                $session->setStatut(Session::STATUT_EN_COURS);
                $em->flush();
                
                $this->addFlash('success', 'La session a démarré.');
            } else {
                $this->addFlash('warning', 'Impossible de démarrer cette session.');
            }
        }
        
        return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
    }

    /**
     * Termine une session
     */
    #[Route('/{id}/terminer', name: 'app_session_terminer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function terminer(Request $request, Session $session, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('terminer' . $session->getId(), $request->request->get('_token'))) {
            if ($session->getStatut() === Session::STATUT_EN_COURS) {
                $session->setStatut(Session::STATUT_TERMINEE);
                $em->flush();
                
                $this->addFlash('success', 'La session est maintenant terminée.');
            } else {
                $this->addFlash('warning', 'Impossible de terminer cette session.');
            }
        }
        
        return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
    }

    /**
     * Annule une session
     */
    #[Route('/{id}/annuler', name: 'app_session_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function annuler(Request $request, Session $session, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('annuler' . $session->getId(), $request->request->get('_token'))) {
            $statutsAnnulables = [
                Session::STATUT_PLANIFIEE,
                Session::STATUT_INSCRIPTIONS_OUVERTES
            ];
            
            if (in_array($session->getStatut(), $statutsAnnulables)) {
                $session->setStatut(Session::STATUT_ANNULEE);
                $em->flush();
                
                $this->addFlash('warning', 'La session a été annulée.');
            } else {
                $this->addFlash('error', 'Impossible d\'annuler une session en cours ou terminée.');
            }
        }
        
        return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
    }

    // ========================================
    // DUPLICATION
    // ========================================

    /**
     * Duplique une session existante pour l'année suivante
     */
    #[Route('/{id}/dupliquer', name: 'app_session_dupliquer', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function dupliquer(Request $request, Session $sessionSource, EntityManagerInterface $em, SessionRepository $sessionRepository): Response
    {
        // Créer une copie
        $session = new Session();
        
        // Copier les propriétés de base
        $session->setFormation($sessionSource->getFormation());
        $session->setModalite($sessionSource->getModalite());
        $session->setLieu($sessionSource->getLieu());
        $session->setEffectifMin($sessionSource->getEffectifMin());
        $session->setEffectifMax($sessionSource->getEffectifMax());
        $session->setCouleur($sessionSource->getCouleur());
        $session->setResponsable($sessionSource->getResponsable());
        
        // Copier les formateurs
        foreach ($sessionSource->getFormateurs() as $formateur) {
            $session->addFormateur($formateur);
        }
        
        // Décaler les dates d'un an
        $dateDebut = clone $sessionSource->getDateDebut();
        $dateFin = clone $sessionSource->getDateFin();
        $dateDebut->modify('+1 year');
        $dateFin->modify('+1 year');
        
        $session->setDateDebut($dateDebut);
        $session->setDateFin($dateFin);
        
        // Générer un nouveau libellé et code
        $anneeDebut = $dateDebut->format('Y');
        $anneeFin = $dateFin->format('Y');
        
        $libelleBase = preg_replace('/\d{4}[-–]\d{4}/', '', $sessionSource->getLibelle());
        $libelleBase = preg_replace('/\d{4}/', '', $libelleBase);
        $libelleBase = trim($libelleBase, ' -–');
        
        $session->setLibelle($libelleBase . ' - Promotion ' . $anneeDebut . '-' . $anneeFin);
        $session->setCode($session->generateCode());
        
        // Statut initial
        $session->setStatut(Session::STATUT_PLANIFIEE);
        $session->setActif(true);
        
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$sessionRepository->isCodeUnique($session->getCode())) {
                // Ajouter un suffixe pour rendre unique
                $session->setCode($session->getCode() . '-' . uniqid());
            }
            
            $em->persist($session);
            $em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été créée par duplication.',
                $session->getLibelle()
            ));
            
            return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
        }
        
        return $this->render('session/dupliquer.html.twig', [
            'form' => $form,
            'session' => $session,
            'sessionSource' => $sessionSource,
        ]);
    }
}
