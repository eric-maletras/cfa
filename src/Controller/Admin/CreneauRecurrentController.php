<?php

namespace App\Controller\Admin;

use App\Entity\CreneauRecurrent;
use App\Entity\Session;
use App\Form\CreneauRecurrentType;
use App\Repository\CreneauRecurrentRepository;
use App\Repository\SessionRepository;
use App\Service\GenerateurSeancesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur CRUD pour les créneaux récurrents
 * 
 * Gère la création, modification et suppression des créneaux horaires
 * qui servent de modèle pour générer les séances planifiées.
 */
#[Route('/admin/planning/creneaux', name: 'admin_creneau_')]
#[IsGranted('ROLE_ADMIN')]
class CreneauRecurrentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CreneauRecurrentRepository $creneauRepository,
        private SessionRepository $sessionRepository,
        private GenerateurSeancesService $generateurSeances,
    ) {
    }

    // ========================================
    // INDEX - Liste des créneaux
    // ========================================
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Filtrer par session si demandé
        $sessionId = $request->query->get('session');
        $session = $sessionId ? $this->sessionRepository->find($sessionId) : null;

        if ($session) {
            $creneaux = $this->creneauRepository->findBy(
                ['session' => $session],
                ['jourSemaine' => 'ASC', 'heureDebut' => 'ASC']
            );
        } else {
            // Grouper par session
            $creneaux = $this->creneauRepository->findBy(
                [],
                ['session' => 'ASC', 'jourSemaine' => 'ASC', 'heureDebut' => 'ASC']
            );
        }

        // Grouper les créneaux par session pour l'affichage
        $creneauxParSession = [];
        foreach ($creneaux as $creneau) {
            $sessionId = $creneau->getSession()->getId();
            if (!isset($creneauxParSession[$sessionId])) {
                $creneauxParSession[$sessionId] = [
                    'session' => $creneau->getSession(),
                    'creneaux' => [],
                ];
            }
            $creneauxParSession[$sessionId]['creneaux'][] = $creneau;
        }

        // Sessions actives pour le filtre
        $sessions = $this->sessionRepository->findBy(['actif' => true], ['dateDebut' => 'DESC']);

        return $this->render('admin/creneau_recurrent/index.html.twig', [
            'creneauxParSession' => $creneauxParSession,
            'sessions' => $sessions,
            'sessionFiltre' => $session,
        ]);
    }

    // ========================================
    // NEW - Créer un créneau
    // ========================================
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $creneau = new CreneauRecurrent();
        $creneau->setActif(true);
        
        // Valeurs par défaut pour les heures
        $creneau->setHeureDebut(new \DateTime('09:00'));
        $creneau->setHeureFin(new \DateTime('12:00'));
        
        // Valeurs par défaut pour les dates (année scolaire courante)
        $now = new \DateTime();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('m');
        // Si on est entre janvier et août, on est dans l'année scolaire commencée l'année précédente
        if ($month < 9) {
            $year--;
        }
        $creneau->setDateDebut(new \DateTime("$year-09-01"));
        $creneau->setDateFin(new \DateTime(($year + 1) . "-08-31"));

        // Pré-remplir avec la session si passée en paramètre
        $sessionId = $request->query->get('session');
        if ($sessionId) {
            $session = $this->sessionRepository->find($sessionId);
            if ($session) {
                $creneau->setSession($session);
                $creneau->setDateDebut($session->getDateDebut());
                $creneau->setDateFin($session->getDateFin());
            }
        }

        $form = $this->createForm(CreneauRecurrentType::class, $creneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les conflits
            $conflits = $this->detecterConflits($creneau);
            
            // Si conflits et pas de confirmation forcée
            if (!empty($conflits) && !$request->request->get('_force_save')) {
                // Stocker le créneau en session pour le récupérer après confirmation
                $request->getSession()->set('creneau_pending', serialize($creneau));
                
                return $this->render('admin/creneau_recurrent/conflits.html.twig', [
                    'creneau' => $creneau,
                    'conflits' => $conflits,
                    'mode' => 'new',
                    'form' => $form,
                ]);
            }
            
            $this->em->persist($creneau);
            $this->em->flush();

            $this->addFlash('success', 'Créneau récurrent créé avec succès.');

            return $this->redirectToRoute('admin_creneau_index', [
                'session' => $creneau->getSession()->getId(),
            ]);
        }

        return $this->render('admin/creneau_recurrent/new.html.twig', [
            'creneau' => $creneau,
            'form' => $form,
        ]);
    }

    // ========================================
    // CONFIRM NEW - Confirmer création malgré conflits
    // ========================================
    #[Route('/new/confirm', name: 'new_confirm', methods: ['POST'])]
    public function newConfirm(Request $request): Response
    {
        $serialized = $request->getSession()->get('creneau_pending');
        if (!$serialized) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('admin_creneau_new');
        }
        
        $creneau = unserialize($serialized);
        $request->getSession()->remove('creneau_pending');
        
        if (!$this->isCsrfTokenValid('confirm_creneau', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_creneau_new');
        }
        
        // Réattacher les entités au EntityManager
        $creneau = $this->reattachCreneau($creneau);
        
        $this->em->persist($creneau);
        $this->em->flush();

        $this->addFlash('warning', 'Créneau créé malgré les conflits détectés.');

        return $this->redirectToRoute('admin_creneau_index', [
            'session' => $creneau->getSession()->getId(),
        ]);
    }

    // ========================================
    // SHOW - Détails d'un créneau
    // ========================================
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CreneauRecurrent $creneau): Response
    {
        // Vérifier les conflits existants
        $conflits = $this->detecterConflits($creneau);
        
        return $this->render('admin/creneau_recurrent/show.html.twig', [
            'creneau' => $creneau,
            'conflits' => $conflits,
        ]);
    }

    // ========================================
    // EDIT - Modifier un créneau
    // ========================================
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, CreneauRecurrent $creneau): Response
    {
        $form = $this->createForm(CreneauRecurrentType::class, $creneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les conflits
            $conflits = $this->detecterConflits($creneau);
            
            // Si conflits et pas de confirmation forcée
            if (!empty($conflits) && !$request->request->get('_force_save')) {
                return $this->render('admin/creneau_recurrent/conflits.html.twig', [
                    'creneau' => $creneau,
                    'conflits' => $conflits,
                    'mode' => 'edit',
                    'form' => $form,
                ]);
            }
            
            $this->em->flush();

            $this->addFlash('success', 'Créneau récurrent modifié avec succès.');

            return $this->redirectToRoute('admin_creneau_index', [
                'session' => $creneau->getSession()->getId(),
            ]);
        }

        return $this->render('admin/creneau_recurrent/edit.html.twig', [
            'creneau' => $creneau,
            'form' => $form,
        ]);
    }

    // ========================================
    // CONFIRM EDIT - Confirmer modification malgré conflits
    // ========================================
    #[Route('/{id}/edit/confirm', name: 'edit_confirm', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function editConfirm(Request $request, CreneauRecurrent $creneau): Response
    {
        if (!$this->isCsrfTokenValid('confirm_creneau', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_creneau_edit', ['id' => $creneau->getId()]);
        }
        
        $this->em->flush();

        $this->addFlash('warning', 'Créneau modifié malgré les conflits détectés.');

        return $this->redirectToRoute('admin_creneau_index', [
            'session' => $creneau->getSession()->getId(),
        ]);
    }

    // ========================================
    // DELETE - Supprimer un créneau
    // ========================================
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, CreneauRecurrent $creneau): Response
    {
        $sessionId = $creneau->getSession()->getId();

        if ($this->isCsrfTokenValid('delete' . $creneau->getId(), $request->request->get('_token'))) {
            $this->em->remove($creneau);
            $this->em->flush();

            $this->addFlash('success', 'Créneau récurrent supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_creneau_index', [
            'session' => $sessionId,
        ]);
    }

    // ========================================
    // TOGGLE - Activer/Désactiver un créneau
    // ========================================
    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, CreneauRecurrent $creneau): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $creneau->getId(), $request->request->get('_token'))) {
            $creneau->setActif(!$creneau->isActif());
            $this->em->flush();

            $status = $creneau->isActif() ? 'activé' : 'désactivé';
            $this->addFlash('success', sprintf('Créneau %s.', $status));
        }

        return $this->redirectToRoute('admin_creneau_index', [
            'session' => $creneau->getSession()->getId(),
        ]);
    }

    // ========================================
    // DUPLICATE - Dupliquer un créneau
    // ========================================
    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(Request $request, CreneauRecurrent $creneau): Response
    {
        if ($this->isCsrfTokenValid('duplicate' . $creneau->getId(), $request->request->get('_token'))) {
            $newCreneau = clone $creneau;
            $newCreneau->setActif(false); // Désactivé par défaut

            $this->em->persist($newCreneau);
            $this->em->flush();

            $this->addFlash('success', 'Créneau dupliqué. Pensez à modifier les paramètres.');

            return $this->redirectToRoute('admin_creneau_edit', [
                'id' => $newCreneau->getId(),
            ]);
        }

        return $this->redirectToRoute('admin_creneau_index');
    }

    // ========================================
    // GENERER - Prévisualisation de la génération
    // ========================================
    #[Route('/{id}/generer', name: 'generer', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generer(CreneauRecurrent $creneau): Response
    {
        // Vérifier que le créneau est actif
        if (!$creneau->isActif()) {
            $this->addFlash('warning', 'Ce créneau est inactif. Activez-le avant de générer des séances.');
            return $this->redirectToRoute('admin_creneau_show', ['id' => $creneau->getId()]);
        }
        
        // Prévisualiser
        $preview = $this->generateurSeances->previsualiser($creneau);
        $nbSeancesExistantes = $this->generateurSeances->countSeances($creneau);
        $hasModifiees = $this->generateurSeances->hasSeancesModifiees($creneau);
        
        return $this->render('admin/creneau_recurrent/generer.html.twig', [
            'creneau' => $creneau,
            'preview' => $preview,
            'nbSeancesExistantes' => $nbSeancesExistantes,
            'hasModifiees' => $hasModifiees,
        ]);
    }

    // ========================================
    // GENERER CONFIRM - Exécuter la génération
    // ========================================
    #[Route('/{id}/generer/confirm', name: 'generer_confirm', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function genererConfirm(Request $request, CreneauRecurrent $creneau): Response
    {
        if (!$this->isCsrfTokenValid('generer' . $creneau->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_creneau_generer', ['id' => $creneau->getId()]);
        }
        
        // Vérifier que le créneau est actif
        if (!$creneau->isActif()) {
            $this->addFlash('error', 'Ce créneau est inactif.');
            return $this->redirectToRoute('admin_creneau_show', ['id' => $creneau->getId()]);
        }
        
        $regenerer = $request->request->getBoolean('regenerer', false);
        
        try {
            $stats = $this->generateurSeances->generer($creneau, $regenerer);
            
            $message = sprintf(
                '%d séance(s) créée(s), %d ignorée(s)',
                $stats['creees'],
                $stats['ignorees']
            );
            
            if ($stats['supprimees'] > 0) {
                $message .= sprintf(', %d supprimée(s)', $stats['supprimees']);
            }
            
            $this->addFlash('success', $message);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_creneau_show', ['id' => $creneau->getId()]);
    }

    // ========================================
    // API - Données dynamiques pour le formulaire
    // ========================================
    #[Route('/api/session/{id}/data', name: 'api_session_data', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiSessionData(Session $session): JsonResponse
    {
        // Matières de la session
        $matieres = [];
        foreach ($session->getSessionMatieres() as $sm) {
            if ($sm->isActif()) {
                $matiere = $sm->getMatiere();
                $matieres[] = [
                    'id' => $sm->getId(),
                    'code' => $matiere->getCode(),
                    'libelle' => $matiere->getLibelle(),
                    'label' => sprintf('%s - %s', $matiere->getCode(), $matiere->getLibelle()),
                ];
            }
        }

        // Formateurs de la session
        $formateurs = [];
        foreach ($session->getFormateurs() as $formateur) {
            $formateurs[] = [
                'id' => $formateur->getId(),
                'nom' => $formateur->getNomComplet(),
            ];
        }

        return $this->json([
            'session' => [
                'id' => $session->getId(),
                'code' => $session->getCode(),
                'dateDebut' => $session->getDateDebut()->format('Y-m-d'),
                'dateFin' => $session->getDateFin()->format('Y-m-d'),
            ],
            'matieres' => $matieres,
            'formateurs' => $formateurs,
        ]);
    }

    // ========================================
    // MÉTHODES PRIVÉES - Détection des conflits
    // ========================================
    
    /**
     * Détecte les conflits de salle et de formateurs pour un créneau
     * 
     * @return array{salle: array, formateurs: array}
     */
    private function detecterConflits(CreneauRecurrent $creneau): array
    {
        $conflits = [
            'salle' => [],
            'formateurs' => [],
        ];
        
        // Ne vérifier que si le créneau est actif
        if (!$creneau->isActif()) {
            return $conflits;
        }
        
        // Vérifier que les données requises sont présentes
        if (!$creneau->getJourSemaine() || 
            !$creneau->getHeureDebut() || 
            !$creneau->getHeureFin() ||
            !$creneau->getDateDebut() ||
            !$creneau->getDateFin()) {
            return $conflits;
        }
        
        // Conflits de salle
        if ($creneau->getSalle()) {
            $conflitsSalle = $this->creneauRepository->findConflitsSalle(
                $creneau->getSalle(),
                $creneau->getJourSemaine(),
                $creneau->getHeureDebut(),
                $creneau->getHeureFin(),
                $creneau->getDateDebut(),
                $creneau->getDateFin(),
                $creneau->getSemaineType(),
                $creneau->getId()
            );
            foreach ($conflitsSalle as $c) {
                $conflits['salle'][] = [
                    'creneau' => $c,
                    'session' => $c->getSession(),
                    'message' => sprintf(
                        'La salle %s est déjà utilisée le %s de %s à %s pour la session %s',
                        $creneau->getSalle()->getCode(),
                        $c->getJourSemaine()->getLibelle(),
                        $c->getHeureDebut()->format('H:i'),
                        $c->getHeureFin()->format('H:i'),
                        $c->getSession()->getCode()
                    ),
                ];
            }
        }
        
        // Conflits de formateurs
        foreach ($creneau->getFormateurs() as $formateur) {
            $conflitsFormateur = $this->creneauRepository->findConflitsFormateur(
                $formateur,
                $creneau->getJourSemaine(),
                $creneau->getHeureDebut(),
                $creneau->getHeureFin(),
                $creneau->getDateDebut(),
                $creneau->getDateFin(),
                $creneau->getSemaineType(),
                $creneau->getId()
            );
            foreach ($conflitsFormateur as $c) {
                $conflits['formateurs'][] = [
                    'creneau' => $c,
                    'formateur' => $formateur,
                    'session' => $c->getSession(),
                    'message' => sprintf(
                        '%s est déjà assigné(e) le %s de %s à %s pour la session %s',
                        $formateur->getNomComplet(),
                        $c->getJourSemaine()->getLibelle(),
                        $c->getHeureDebut()->format('H:i'),
                        $c->getHeureFin()->format('H:i'),
                        $c->getSession()->getCode()
                    ),
                ];
            }
        }
        
        return $conflits;
    }
    
    /**
     * Réattache les entités d'un créneau désérialisé au EntityManager
     */
    private function reattachCreneau(CreneauRecurrent $creneau): CreneauRecurrent
    {
        $newCreneau = new CreneauRecurrent();
        
        // Session
        if ($creneau->getSession()) {
            $session = $this->sessionRepository->find($creneau->getSession()->getId());
            $newCreneau->setSession($session);
        }
        
        // Propriétés simples
        $newCreneau->setJourSemaine($creneau->getJourSemaine());
        $newCreneau->setSemaineType($creneau->getSemaineType());
        $newCreneau->setHeureDebut($creneau->getHeureDebut());
        $newCreneau->setHeureFin($creneau->getHeureFin());
        $newCreneau->setDateDebut($creneau->getDateDebut());
        $newCreneau->setDateFin($creneau->getDateFin());
        $newCreneau->setActif($creneau->isActif());
        $newCreneau->setCommentaire($creneau->getCommentaire());
        
        // Salle
        if ($creneau->getSalle()) {
            $salle = $this->em->getRepository($creneau->getSalle()::class)->find($creneau->getSalle()->getId());
            $newCreneau->setSalle($salle);
        }
        
        // SessionMatiere
        if ($creneau->getSessionMatiere()) {
            $sm = $this->em->getRepository($creneau->getSessionMatiere()::class)->find($creneau->getSessionMatiere()->getId());
            $newCreneau->setSessionMatiere($sm);
        }
        
        // Formateurs
        foreach ($creneau->getFormateurs() as $formateur) {
            $f = $this->em->getRepository($formateur::class)->find($formateur->getId());
            if ($f) {
                $newCreneau->addFormateur($f);
            }
        }
        
        return $newCreneau;
    }
}
