<?php

namespace App\Controller\Admin;

use App\Entity\CreneauRecurrent;
use App\Entity\Session;
use App\Form\CreneauRecurrentType;
use App\Repository\CreneauRecurrentRepository;
use App\Service\ConflitPlanningService;
use App\Service\GenerateurSeancesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur CRUD pour les créneaux récurrents d'une session
 */
#[Route('/admin/sessions/{sessionId}/creneaux')]
#[IsGranted('ROLE_ADMIN')]
class SessionPlanningController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CreneauRecurrentRepository $creneauRepository,
        private GenerateurSeancesService $generateurService,
        private ConflitPlanningService $conflitService,
    ) {
    }

    /**
     * Liste des créneaux récurrents d'une session
     */
    #[Route('', name: 'admin_session_creneaux_index', methods: ['GET'])]
    public function index(Session $sessionId): Response
    {
        $session = $sessionId;
        
        $creneaux = $this->creneauRepository->findBySession($session, false);
        
        // Calculer les statistiques de conflits et séances pour chaque créneau
        $stats = [];
        foreach ($creneaux as $creneau) {
            $stats[$creneau->getId()] = [
                'nbSeances' => $creneau->getNombreSeances(),
                'nbConflits' => $this->conflitService->compterConflits($creneau),
                'estimation' => $this->generateurService->estimerNombreSeances($creneau),
            ];
        }

        return $this->render('admin/session_creneaux/index.html.twig', [
            'session' => $session,
            'creneaux' => $creneaux,
            'stats' => $stats,
        ]);
    }

    /**
     * Création d'un nouveau créneau
     */
    #[Route('/new', name: 'admin_session_creneaux_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Session $sessionId): Response
    {
        $session = $sessionId;
        
        $creneau = new CreneauRecurrent();
        $creneau->setSession($session);
        
        $form = $this->createForm(CreneauRecurrentType::class, $creneau, [
            'session' => $session,
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les conflits
            $conflits = $this->conflitService->validerCreneau($creneau);
            $hasConflits = !empty($conflits['salles']) || !empty($conflits['formateurs']);
            
            if ($hasConflits && !$request->request->get('force_save')) {
                foreach ($conflits['salles'] as $conflit) {
                    $this->addFlash('warning', $conflit['message']);
                }
                foreach ($conflits['formateurs'] as $conflit) {
                    $this->addFlash('warning', $conflit['message']);
                }
                $this->addFlash('info', 'Des conflits ont été détectés. Soumettez à nouveau pour enregistrer malgré tout.');
                
                return $this->render('admin/session_creneaux/new.html.twig', [
                    'session' => $session,
                    'creneau' => $creneau,
                    'form' => $form,
                    'show_force_button' => true,
                ]);
            }
            
            $this->entityManager->persist($creneau);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Créneau créé avec succès.');
            
            return $this->redirectToRoute('admin_session_creneaux_edit', [
                'sessionId' => $session->getId(),
                'id' => $creneau->getId(),
            ]);
        }
        
        return $this->render('admin/session_creneaux/new.html.twig', [
            'session' => $session,
            'creneau' => $creneau,
            'form' => $form,
            'show_force_button' => false,
        ]);
    }

    /**
     * Modification d'un créneau
     */
    #[Route('/{id}/edit', name: 'admin_session_creneaux_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Session $sessionId, CreneauRecurrent $id): Response
    {
        $session = $sessionId;
        $creneau = $id;
        
        if ($creneau->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Créneau non trouvé pour cette session.');
        }
        
        $form = $this->createForm(CreneauRecurrentType::class, $creneau, [
            'session' => $session,
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $conflits = $this->conflitService->validerCreneau($creneau);
            $hasConflits = !empty($conflits['salles']) || !empty($conflits['formateurs']);
            
            if ($hasConflits && !$request->request->get('force_save')) {
                foreach ($conflits['salles'] as $conflit) {
                    $this->addFlash('warning', $conflit['message']);
                }
                foreach ($conflits['formateurs'] as $conflit) {
                    $this->addFlash('warning', $conflit['message']);
                }
                $this->addFlash('info', 'Des conflits ont été détectés. Soumettez à nouveau pour enregistrer malgré tout.');
                
                return $this->render('admin/session_creneaux/edit.html.twig', [
                    'session' => $session,
                    'creneau' => $creneau,
                    'form' => $form,
                    'show_force_button' => true,
                    'stats' => $this->getCreneauStats($creneau),
                ]);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Créneau modifié avec succès.');
            
            return $this->redirectToRoute('admin_session_creneaux_index', [
                'sessionId' => $session->getId(),
            ]);
        }
        
        return $this->render('admin/session_creneaux/edit.html.twig', [
            'session' => $session,
            'creneau' => $creneau,
            'form' => $form,
            'show_force_button' => false,
            'stats' => $this->getCreneauStats($creneau),
        ]);
    }

    /**
     * Suppression d'un créneau
     */
    #[Route('/{id}/delete', name: 'admin_session_creneaux_delete', methods: ['POST'])]
    public function delete(Request $request, Session $sessionId, CreneauRecurrent $id): Response
    {
        $session = $sessionId;
        $creneau = $id;
        
        if ($creneau->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Créneau non trouvé pour cette session.');
        }
        
        if ($this->isCsrfTokenValid('delete' . $creneau->getId(), $request->request->get('_token'))) {
            $nbSeances = $creneau->getNombreSeances();
            
            $this->entityManager->remove($creneau);
            $this->entityManager->flush();
            
            if ($nbSeances > 0) {
                $this->addFlash('success', sprintf(
                    'Créneau supprimé avec ses %d séance(s) associée(s).',
                    $nbSeances
                ));
            } else {
                $this->addFlash('success', 'Créneau supprimé.');
            }
        }
        
        return $this->redirectToRoute('admin_session_creneaux_index', [
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * Génère les séances pour un créneau
     */
    #[Route('/{id}/generate', name: 'admin_session_creneaux_generate', methods: ['POST'])]
    public function generate(Request $request, Session $sessionId, CreneauRecurrent $id): Response
    {
        $session = $sessionId;
        $creneau = $id;
        
        if ($creneau->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Créneau non trouvé pour cette session.');
        }
        
        if ($this->isCsrfTokenValid('generate' . $creneau->getId(), $request->request->get('_token'))) {
            $regenerer = $request->request->getBoolean('regenerer', false);
            
            $nbCreees = $this->generateurService->generer($creneau, $regenerer);
            
            if ($nbCreees > 0) {
                $this->addFlash('success', sprintf(
                    '%d séance(s) générée(s) pour le créneau "%s".',
                    $nbCreees,
                    $creneau
                ));
            } else {
                $this->addFlash('info', 'Aucune nouvelle séance à générer (toutes les séances existent déjà ou sont des jours fermés).');
            }
        }
        
        return $this->redirectToRoute('admin_session_creneaux_index', [
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * Génère les séances pour tous les créneaux actifs de la session
     */
    #[Route('/generate-all', name: 'admin_session_creneaux_generate_all', methods: ['POST'])]
    public function generateAll(Request $request, Session $sessionId): Response
    {
        $session = $sessionId;
        
        if ($this->isCsrfTokenValid('generate_all' . $session->getId(), $request->request->get('_token'))) {
            $creneaux = $this->creneauRepository->findBySession($session, true);
            $regenerer = $request->request->getBoolean('regenerer', false);
            
            $totalSeances = 0;
            $creneauxTraites = 0;
            
            foreach ($creneaux as $creneau) {
                $nb = $this->generateurService->generer($creneau, $regenerer);
                $totalSeances += $nb;
                if ($nb > 0) {
                    $creneauxTraites++;
                }
            }
            
            if ($totalSeances > 0) {
                $this->addFlash('success', sprintf(
                    '%d séance(s) générée(s) pour %d créneau(x).',
                    $totalSeances,
                    $creneauxTraites
                ));
            } else {
                $this->addFlash('info', 'Aucune nouvelle séance à générer.');
            }
        }
        
        return $this->redirectToRoute('admin_session_creneaux_index', [
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * Prévisualisation des séances à générer
     */
    #[Route('/{id}/preview', name: 'admin_session_creneaux_preview', methods: ['GET'])]
    public function preview(Session $sessionId, CreneauRecurrent $id): Response
    {
        $session = $sessionId;
        $creneau = $id;
        
        if ($creneau->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Créneau non trouvé pour cette session.');
        }
        
        $preview = $this->generateurService->previsualiser($creneau);
        $conflits = $this->conflitService->validerCreneau($creneau);
        
        return $this->render('admin/session_creneaux/preview.html.twig', [
            'session' => $session,
            'creneau' => $creneau,
            'preview' => $preview,
            'conflits' => $conflits,
        ]);
    }

    private function getCreneauStats(CreneauRecurrent $creneau): array
    {
        return [
            'nbSeances' => $creneau->getNombreSeances(),
            'nbSeancesNonModifiees' => $creneau->getNombreSeancesNonModifiees(),
            'nbConflits' => $this->conflitService->compterConflits($creneau),
            'estimation' => $this->generateurService->estimerNombreSeances($creneau),
            'messagesConflits' => $this->conflitService->getMessagesConflits($creneau),
        ];
    }
}
