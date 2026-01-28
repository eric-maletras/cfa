<?php

namespace App\Controller\Admin;

use App\Entity\Session;
use App\Entity\SessionMatiere;
use App\Entity\SessionMatiereFormateur;
use App\Entity\User;
use App\Repository\SessionMatiereFormateurRepository;
use App\Repository\SessionMatiereRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des formateurs assignés aux matières d'une session
 * 
 * Permet d'assigner des formateurs (parmi ceux de la session) aux matières,
 * de gérer les heures assignées et de désigner un responsable par matière.
 */
#[Route('/admin/sessions/{sessionId}/matieres')]
#[IsGranted('ROLE_ADMIN')]
class SessionMatiereFormateursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionMatiereRepository $sessionMatiereRepo,
        private SessionMatiereFormateurRepository $smfRepo,
        private UserRepository $userRepo
    ) {}

    // ========================================
    // VUE D'ENSEMBLE DES ASSIGNATIONS
    // ========================================

    /**
     * Vue d'ensemble des assignations formateurs/matières pour une session
     */
    #[Route('/formateurs', name: 'admin_session_matieres_formateurs', methods: ['GET'])]
    public function overview(
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        $sessionMatieres = $this->sessionMatiereRepo->findBySession($session);
        $formateursGrouped = $this->smfRepo->getFormateursGroupedBySessionMatiere($session);
        $stats = $this->smfRepo->getStatistiquesSession($session);
        
        // Formateurs de la session pour le dropdown
        $formateursSession = $session->getFormateurs()->toArray();

        return $this->render('admin/session_matieres/formateurs_overview.html.twig', [
            'session' => $session,
            'sessionMatieres' => $sessionMatieres,
            'formateursGrouped' => $formateursGrouped,
            'formateursSession' => $formateursSession,
            'stats' => $stats,
        ]);
    }

    // ========================================
    // GESTION DES FORMATEURS D'UNE MATIÈRE
    // ========================================

    /**
     * Affiche et gère les formateurs d'une matière spécifique
     */
    #[Route('/{id}/formateurs', name: 'admin_session_matiere_formateurs', methods: ['GET'])]
    public function matiereFormateurs(
        #[MapEntity(id: 'sessionId')] Session $session,
        SessionMatiere $sessionMatiere
    ): Response {
        // Vérification de cohérence
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }

        $assignations = $this->smfRepo->findBySessionMatiere($sessionMatiere);
        $formateursDisponibles = $this->smfRepo->findFormateursDisponibles($sessionMatiere);

        return $this->render('admin/session_matieres/formateurs_matiere.html.twig', [
            'session' => $session,
            'sessionMatiere' => $sessionMatiere,
            'assignations' => $assignations,
            'formateursDisponibles' => $formateursDisponibles,
        ]);
    }

    // ========================================
    // AJOUTER UN FORMATEUR À UNE MATIÈRE
    // ========================================

    /**
     * Assigne un formateur à une matière de session
     */
    #[Route('/{id}/formateurs/add', name: 'admin_session_matiere_formateur_add', methods: ['POST'])]
    public function addFormateur(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        SessionMatiere $sessionMatiere
    ): Response {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_formateur' . $sessionMatiere->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        $formateurId = $request->request->get('formateur_id');
        if (!$formateurId) {
            $this->addFlash('error', 'Veuillez sélectionner un formateur.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        $formateur = $this->userRepo->find($formateurId);
        if (!$formateur) {
            $this->addFlash('error', 'Formateur introuvable.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        // Vérifier que le formateur fait partie de la session
        if (!$session->getFormateurs()->contains($formateur)) {
            $this->addFlash('error', 'Ce formateur n\'est pas assigné à cette session.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        // Vérifier qu'il n'est pas déjà assigné
        if ($this->smfRepo->isFormateurAssigne($sessionMatiere, $formateur)) {
            $this->addFlash('warning', 'Ce formateur est déjà assigné à cette matière.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        // Créer l'assignation
        $smf = new SessionMatiereFormateur();
        $smf->setSessionMatiere($sessionMatiere);
        $smf->setFormateur($formateur);
        
        // Heures assignées (optionnel)
        $heures = $request->request->get('heures_assignees');
        if ($heures !== null && $heures !== '') {
            $smf->setHeuresAssignees((int) $heures);
        }

        // Responsable (si c'est le premier ou si demandé)
        $estResponsable = $request->request->getBoolean('est_responsable');
        if ($estResponsable) {
            // Retirer le statut des autres
            $this->smfRepo->clearResponsable($sessionMatiere);
            $smf->setEstResponsable(true);
        }

        $this->em->persist($smf);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            '%s a été assigné(e) à la matière %s.',
            $formateur->getNomComplet(),
            $sessionMatiere->getMatiere()->getCode()
        ));

        return $this->redirectBack($request, $session, $sessionMatiere);
    }

    // ========================================
    // MODIFIER UNE ASSIGNATION
    // ========================================

    /**
     * Modifie une assignation existante (heures, responsable)
     */
    #[Route('/{smId}/formateurs/{smfId}/edit', name: 'admin_session_matiere_formateur_edit', methods: ['POST'])]
    public function editFormateur(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        #[MapEntity(id: 'smId')] SessionMatiere $sessionMatiere,
        #[MapEntity(id: 'smfId')] SessionMatiereFormateur $smf
    ): Response {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }
        
        if ($smf->getSessionMatiere()->getId() !== $sessionMatiere->getId()) {
            throw $this->createNotFoundException('Cette assignation n\'appartient pas à cette matière.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('edit_formateur' . $smf->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        // Mise à jour des heures
        $heures = $request->request->get('heures_assignees');
        if ($heures !== null && $heures !== '') {
            $smf->setHeuresAssignees((int) $heures);
        } else {
            $smf->setHeuresAssignees(null);
        }

        // Mise à jour du commentaire
        $commentaire = $request->request->get('commentaire');
        $smf->setCommentaire($commentaire ?: null);

        // Mise à jour du statut responsable
        $estResponsable = $request->request->getBoolean('est_responsable');
        if ($estResponsable && !$smf->isEstResponsable()) {
            $this->smfRepo->clearResponsable($sessionMatiere, $smf->getId());
            $smf->setEstResponsable(true);
        } elseif (!$estResponsable) {
            $smf->setEstResponsable(false);
        }

        $this->em->flush();

        $this->addFlash('success', 'Assignation mise à jour.');

        return $this->redirectBack($request, $session, $sessionMatiere);
    }

    // ========================================
    // DÉFINIR LE RESPONSABLE
    // ========================================

    /**
     * Définit un formateur comme responsable de la matière
     */
    #[Route('/{smId}/formateurs/{smfId}/set-responsable', name: 'admin_session_matiere_formateur_responsable', methods: ['POST'])]
    public function setResponsable(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        #[MapEntity(id: 'smId')] SessionMatiere $sessionMatiere,
        #[MapEntity(id: 'smfId')] SessionMatiereFormateur $smf
    ): Response {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }
        
        if ($smf->getSessionMatiere()->getId() !== $sessionMatiere->getId()) {
            throw $this->createNotFoundException('Cette assignation n\'appartient pas à cette matière.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('responsable' . $smf->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        // Retirer le statut des autres et l'assigner
        $this->smfRepo->clearResponsable($sessionMatiere);
        $smf->setEstResponsable(true);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            '%s est maintenant responsable de la matière %s.',
            $smf->getFormateur()->getNomComplet(),
            $sessionMatiere->getMatiere()->getCode()
        ));

        return $this->redirectBack($request, $session, $sessionMatiere);
    }

    // ========================================
    // RETIRER UN FORMATEUR
    // ========================================

    /**
     * Retire un formateur d'une matière
     */
    #[Route('/{smId}/formateurs/{smfId}/remove', name: 'admin_session_matiere_formateur_remove', methods: ['POST'])]
    public function removeFormateur(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        #[MapEntity(id: 'smId')] SessionMatiere $sessionMatiere,
        #[MapEntity(id: 'smfId')] SessionMatiereFormateur $smf
    ): Response {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }
        
        if ($smf->getSessionMatiere()->getId() !== $sessionMatiere->getId()) {
            throw $this->createNotFoundException('Cette assignation n\'appartient pas à cette matière.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('remove_formateur' . $smf->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectBack($request, $session, $sessionMatiere);
        }

        $formateurNom = $smf->getFormateur()->getNomComplet();
        $matiereCode = $sessionMatiere->getMatiere()->getCode();

        $this->em->remove($smf);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            '%s a été retiré(e) de la matière %s.',
            $formateurNom,
            $matiereCode
        ));

        return $this->redirectBack($request, $session, $sessionMatiere);
    }

    // ========================================
    // ASSIGNATION RAPIDE (depuis la vue d'ensemble)
    // ========================================

    /**
     * Assignation rapide d'un formateur à une matière (AJAX)
     */
    #[Route('/{id}/formateurs/quick-add', name: 'admin_session_matiere_formateur_quick_add', methods: ['POST'])]
    public function quickAdd(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        SessionMatiere $sessionMatiere
    ): JsonResponse {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            return $this->json(['success' => false, 'message' => 'Matière invalide.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $formateurId = $data['formateur_id'] ?? null;

        if (!$formateurId) {
            return $this->json(['success' => false, 'message' => 'Formateur requis.'], 400);
        }

        $formateur = $this->userRepo->find($formateurId);
        if (!$formateur) {
            return $this->json(['success' => false, 'message' => 'Formateur introuvable.'], 404);
        }

        if (!$session->getFormateurs()->contains($formateur)) {
            return $this->json(['success' => false, 'message' => 'Formateur non assigné à la session.'], 400);
        }

        if ($this->smfRepo->isFormateurAssigne($sessionMatiere, $formateur)) {
            return $this->json(['success' => false, 'message' => 'Déjà assigné.'], 400);
        }

        $smf = new SessionMatiereFormateur();
        $smf->setSessionMatiere($sessionMatiere);
        $smf->setFormateur($formateur);
        
        if (isset($data['heures_assignees'])) {
            $smf->setHeuresAssignees((int) $data['heures_assignees']);
        }

        $this->em->persist($smf);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Formateur assigné.',
            'data' => [
                'id' => $smf->getId(),
                'formateur' => [
                    'id' => $formateur->getId(),
                    'nom' => $formateur->getNomComplet(),
                    'initiales' => strtoupper(
                        substr($formateur->getPrenom(), 0, 1) . 
                        substr($formateur->getNom(), 0, 1)
                    ),
                ],
            ],
        ]);
    }

    /**
     * Retrait rapide d'un formateur (AJAX)
     */
    #[Route('/{smId}/formateurs/{smfId}/quick-remove', name: 'admin_session_matiere_formateur_quick_remove', methods: ['DELETE'])]
    public function quickRemove(
        #[MapEntity(id: 'sessionId')] Session $session,
        #[MapEntity(id: 'smId')] SessionMatiere $sessionMatiere,
        #[MapEntity(id: 'smfId')] SessionMatiereFormateur $smf
    ): JsonResponse {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            return $this->json(['success' => false, 'message' => 'Matière invalide.'], 404);
        }
        
        if ($smf->getSessionMatiere()->getId() !== $sessionMatiere->getId()) {
            return $this->json(['success' => false, 'message' => 'Assignation invalide.'], 404);
        }

        $this->em->remove($smf);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Formateur retiré.']);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Détermine la redirection appropriée
     */
    private function redirectBack(Request $request, Session $session, SessionMatiere $sessionMatiere): Response
    {
        $referer = $request->headers->get('referer');
        
        // Si on vient de la vue détaillée d'une matière, y retourner
        if ($referer && str_contains($referer, '/formateurs') && str_contains($referer, (string) $sessionMatiere->getId())) {
            return $this->redirectToRoute('admin_session_matiere_formateurs', [
                'sessionId' => $session->getId(),
                'id' => $sessionMatiere->getId(),
            ]);
        }
        
        // Sinon, retourner à la vue d'ensemble
        return $this->redirectToRoute('admin_session_matieres_formateurs', [
            'sessionId' => $session->getId(),
        ]);
    }
}
