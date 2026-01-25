<?php

namespace App\Controller\Admin;

use App\Entity\Session;
use App\Entity\User;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des formateurs associés aux sessions
 * Gère l'affectation des formateurs et la désignation du responsable pédagogique
 */
#[Route('/admin/sessions')]
#[IsGranted('ROLE_ADMIN')]
class SessionFormateursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionRepository $sessionRepo,
        private UserRepository $userRepo
    ) {}

    // ========================================
    // PAGE PRINCIPALE DE GESTION
    // ========================================

    /**
     * Affiche et gère les formateurs d'une session
     */
    #[Route('/{id}/formateurs', name: 'admin_session_formateurs', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(Session $session): Response
    {
        // Récupérer tous les formateurs disponibles (actifs avec ROLE_FORMATEUR)
        $formateursDisponibles = $this->userRepo->findByRoleCode('ROLE_FORMATEUR');
        
        // Filtrer ceux qui ne sont pas déjà affectés à cette session
        $formateursAffectes = $session->getFormateurs()->toArray();
        $formateursNonAffectes = array_filter(
            $formateursDisponibles,
            fn(User $u) => !in_array($u, $formateursAffectes, true)
        );
        
        return $this->render('admin/sessions/formateurs.html.twig', [
            'session' => $session,
            'formateursAffectes' => $formateursAffectes,
            'formateursDisponibles' => $formateursNonAffectes,
        ]);
    }

    // ========================================
    // AJOUTER UN FORMATEUR
    // ========================================

    /**
     * Ajoute un formateur à la session
     */
    #[Route('/{id}/formateurs/ajouter', name: 'admin_session_formateur_ajouter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ajouter(Request $request, Session $session): Response
    {
        $formateurId = $request->request->get('formateur_id');
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('ajouter_formateur' . $session->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        if (!$formateurId) {
            $this->addFlash('error', 'Veuillez sélectionner un formateur.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        $formateur = $this->userRepo->find($formateurId);
        
        if (!$formateur) {
            $this->addFlash('error', 'Formateur introuvable.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        // Vérifier que c'est bien un formateur
        if (!in_array('ROLE_FORMATEUR', $formateur->getRoles())) {
            $this->addFlash('error', 'Cet utilisateur n\'est pas un formateur.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        // Vérifier qu'il n'est pas déjà affecté
        if ($session->getFormateurs()->contains($formateur)) {
            $this->addFlash('warning', 'Ce formateur est déjà affecté à cette session.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        $session->addFormateur($formateur);
        $this->em->flush();
        
        $this->addFlash('success', sprintf(
            '%s a été ajouté(e) à l\'équipe pédagogique.',
            $formateur->getNomComplet()
        ));
        
        return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
    }

    // ========================================
    // RETIRER UN FORMATEUR
    // ========================================

    /**
     * Retire un formateur de la session
     */
    #[Route('/{id}/formateurs/{formateurId}/retirer', name: 'admin_session_formateur_retirer', methods: ['POST'], requirements: ['id' => '\d+', 'formateurId' => '\d+'])]
    public function retirer(Request $request, Session $session, int $formateurId): Response
    {
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('retirer_formateur' . $formateurId, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        $formateur = $this->userRepo->find($formateurId);
        
        if (!$formateur) {
            $this->addFlash('error', 'Formateur introuvable.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        // Si c'est le responsable, on le retire aussi de ce rôle
        if ($session->getResponsable() === $formateur) {
            $session->setResponsable(null);
            $this->addFlash('info', 'Le responsable pédagogique a été retiré.');
        }
        
        $session->removeFormateur($formateur);
        $this->em->flush();
        
        $this->addFlash('success', sprintf(
            '%s a été retiré(e) de l\'équipe pédagogique.',
            $formateur->getNomComplet()
        ));
        
        return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
    }

    // ========================================
    // DÉFINIR LE RESPONSABLE PÉDAGOGIQUE
    // ========================================

    /**
     * Définit le responsable pédagogique de la session
     */
    #[Route('/{id}/formateurs/responsable', name: 'admin_session_definir_responsable', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function definirResponsable(Request $request, Session $session): Response
    {
        $formateurId = $request->request->get('responsable_id');
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('definir_responsable' . $session->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        // Si aucun formateur sélectionné, on retire le responsable
        if (empty($formateurId)) {
            $session->setResponsable(null);
            $this->em->flush();
            $this->addFlash('info', 'Le responsable pédagogique a été retiré.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        $formateur = $this->userRepo->find($formateurId);
        
        if (!$formateur) {
            $this->addFlash('error', 'Formateur introuvable.');
            return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
        }
        
        // Le responsable doit faire partie des formateurs de la session
        if (!$session->getFormateurs()->contains($formateur)) {
            // On l'ajoute automatiquement aux formateurs
            $session->addFormateur($formateur);
        }
        
        $session->setResponsable($formateur);
        $this->em->flush();
        
        $this->addFlash('success', sprintf(
            '%s est maintenant responsable pédagogique de cette promotion.',
            $formateur->getNomComplet()
        ));
        
        return $this->redirectToRoute('admin_session_formateurs', ['id' => $session->getId()]);
    }

    // ========================================
    // API AJAX - RECHERCHE FORMATEURS
    // ========================================

    /**
     * Recherche des formateurs pour autocomplétion (AJAX)
     */
    #[Route('/formateurs/search', name: 'admin_session_formateurs_search', methods: ['GET'])]
    public function searchFormateurs(Request $request): JsonResponse
    {
        $term = $request->query->get('q', '');
        $sessionId = $request->query->get('session_id');
        
        if (strlen($term) < 2) {
            return $this->json([]);
        }
        
        // Rechercher les formateurs correspondants
        $formateurs = $this->userRepo->searchFormateurs($term);
        
        // Si une session est spécifiée, exclure les formateurs déjà affectés
        if ($sessionId) {
            $session = $this->sessionRepo->find($sessionId);
            if ($session) {
                $affectes = $session->getFormateurs()->toArray();
                $formateurs = array_filter(
                    $formateurs,
                    fn(User $u) => !in_array($u, $affectes, true)
                );
            }
        }
        
        $results = [];
        foreach ($formateurs as $formateur) {
            $results[] = [
                'id' => $formateur->getId(),
                'text' => $formateur->getNomComplet(),
                'email' => $formateur->getEmail(),
            ];
        }
        
        return $this->json($results);
    }
}
