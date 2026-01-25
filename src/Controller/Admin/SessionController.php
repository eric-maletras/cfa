<?php

namespace App\Controller\Admin;

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
 * Contrôleur de gestion des sessions/promotions de formation
 * Accessible via /module/admin_promotions
 */
#[Route('/admin/sessions')]
#[IsGranted('ROLE_ADMIN')]
class SessionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionRepository $sessionRepo
    ) {}

    // ========================================
    // LISTE
    // ========================================

    /**
     * Liste toutes les sessions avec filtres
     */
    #[Route('', name: 'admin_session_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer les filtres
        $statut = $request->query->get('statut');
        $annee = $request->query->get('annee');
        $search = $request->query->get('q');
        
        // Appliquer les filtres
        if (!empty($search)) {
            $sessions = $this->sessionRepo->search($search);
        } elseif (!empty($statut)) {
            $sessions = $this->sessionRepo->findByStatut($statut);
        } elseif (!empty($annee)) {
            $sessions = $this->sessionRepo->findByAnnee((int) $annee);
        } else {
            $sessions = $this->sessionRepo->findActiveWithFormation();
        }
        
        // Stats pour les badges
        $stats = $this->sessionRepo->countByStatut();
        $annees = $this->sessionRepo->findDistinctAnnees();
        
        return $this->render('admin/sessions/index.html.twig', [
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
    #[Route('/new', name: 'admin_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
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
            if (!$this->sessionRepo->isCodeUnique($session->getCode())) {
                $this->addFlash('error', 'Ce code de session existe déjà.');
                return $this->render('admin/sessions/new.html.twig', [
                    'form' => $form,
                    'session' => $session,
                ]);
            }
            
            $this->em->persist($session);
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été créée avec succès.',
                $session->getLibelle()
            ));
            
            return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
        }
        
        return $this->render('admin/sessions/new.html.twig', [
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
    #[Route('/{id}', name: 'admin_session_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Session $session): Response
    {
        return $this->render('admin/sessions/show.html.twig', [
            'session' => $session,
        ]);
    }

    // ========================================
    // MODIFICATION
    // ========================================

    /**
     * Modification d'une session existante
     */
    #[Route('/{id}/edit', name: 'admin_session_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Session $session): Response
    {
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code (en excluant la session actuelle)
            if (!$this->sessionRepo->isCodeUnique($session->getCode(), $session->getId())) {
                $this->addFlash('error', 'Ce code de session existe déjà.');
                return $this->render('admin/sessions/edit.html.twig', [
                    'form' => $form,
                    'session' => $session,
                ]);
            }
            
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été modifiée avec succès.',
                $session->getLibelle()
            ));
            
            return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
        }
        
        return $this->render('admin/sessions/edit.html.twig', [
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
    #[Route('/{id}/delete', name: 'admin_session_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('delete' . $session->getId(), $request->request->get('_token'))) {
            // Soft delete : on désactive plutôt que supprimer
            $session->setActif(false);
            $session->setStatut(Session::STATUT_ANNULEE);
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été désactivée.',
                $session->getLibelle()
            ));
        }
        
        return $this->redirectToRoute('admin_session_index');
    }

    // ========================================
    // ACTIONS RAPIDES (Changement de statut)
    // ========================================

    /**
     * Ouvre les inscriptions pour une session
     */
    #[Route('/{id}/ouvrir-inscriptions', name: 'admin_session_ouvrir_inscriptions', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ouvrirInscriptions(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('ouvrir' . $session->getId(), $request->request->get('_token'))) {
            if ($session->getStatut() === Session::STATUT_PLANIFIEE) {
                $session->setStatut(Session::STATUT_INSCRIPTIONS_OUVERTES);
                $this->em->flush();
                
                $this->addFlash('success', 'Les inscriptions sont maintenant ouvertes.');
            } else {
                $this->addFlash('warning', 'Impossible d\'ouvrir les inscriptions pour cette session.');
            }
        }
        
        return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
    }

    /**
     * Démarre une session (passe en "en cours")
     */
    #[Route('/{id}/demarrer', name: 'admin_session_demarrer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function demarrer(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('demarrer' . $session->getId(), $request->request->get('_token'))) {
            $statutsValides = [Session::STATUT_PLANIFIEE, Session::STATUT_INSCRIPTIONS_OUVERTES];
            
            if (in_array($session->getStatut(), $statutsValides)) {
                $session->setStatut(Session::STATUT_EN_COURS);
                $this->em->flush();
                
                $this->addFlash('success', 'La session a démarré.');
            } else {
                $this->addFlash('warning', 'Impossible de démarrer cette session.');
            }
        }
        
        return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
    }

    /**
     * Termine une session
     */
    #[Route('/{id}/terminer', name: 'admin_session_terminer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function terminer(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('terminer' . $session->getId(), $request->request->get('_token'))) {
            if ($session->getStatut() === Session::STATUT_EN_COURS) {
                $session->setStatut(Session::STATUT_TERMINEE);
                $this->em->flush();
                
                $this->addFlash('success', 'La session est maintenant terminée.');
            } else {
                $this->addFlash('warning', 'Impossible de terminer cette session.');
            }
        }
        
        return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
    }

    /**
     * Annule une session
     */
    #[Route('/{id}/annuler', name: 'admin_session_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function annuler(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('annuler' . $session->getId(), $request->request->get('_token'))) {
            $statutsAnnulables = [
                Session::STATUT_PLANIFIEE,
                Session::STATUT_INSCRIPTIONS_OUVERTES
            ];
            
            if (in_array($session->getStatut(), $statutsAnnulables)) {
                $session->setStatut(Session::STATUT_ANNULEE);
                $this->em->flush();
                
                $this->addFlash('warning', 'La session a été annulée.');
            } else {
                $this->addFlash('error', 'Impossible d\'annuler une session en cours ou terminée.');
            }
        }
        
        return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
    }

    // ========================================
    // DUPLICATION
    // ========================================

    /**
     * Duplique une session existante pour l'année suivante
     */
    #[Route('/{id}/dupliquer', name: 'admin_session_dupliquer', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function dupliquer(Request $request, Session $sessionSource): Response
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
            if (!$this->sessionRepo->isCodeUnique($session->getCode())) {
                // Ajouter un suffixe pour rendre unique
                $session->setCode($session->getCode() . '-' . uniqid());
            }
            
            $this->em->persist($session);
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'La session "%s" a été créée par duplication.',
                $session->getLibelle()
            ));
            
            return $this->redirectToRoute('admin_session_show', ['id' => $session->getId()]);
        }
        
        return $this->render('admin/sessions/dupliquer.html.twig', [
            'form' => $form,
            'session' => $session,
            'sessionSource' => $sessionSource,
        ]);
    }

    // ========================================
    // TOGGLE ACTIF
    // ========================================

    #[Route('/{id}/toggle', name: 'admin_session_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $session->getId(), $request->request->get('_token'))) {
            $session->setActif(!$session->isActif());
            $this->em->flush();
            $this->addFlash('success', 'Statut de la session mis à jour.');
        }

        return $this->redirectToRoute('admin_session_index');
    }
}
