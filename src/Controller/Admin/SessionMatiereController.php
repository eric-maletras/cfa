<?php

namespace App\Controller\Admin;

use App\Entity\Session;
use App\Entity\SessionMatiere;
use App\Form\SessionMatiereType;
use App\Repository\MatiereRepository;
use App\Repository\SessionMatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des matières d'une session
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_ADMIN
 */
#[Route('/admin/sessions/{sessionId}/matieres')]
#[IsGranted('ROLE_ADMIN')]
class SessionMatiereController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionMatiereRepository $sessionMatiereRepo,
        private MatiereRepository $matiereRepo
    ) {}

    /**
     * Liste des matières d'une session
     */
    #[Route('', name: 'admin_session_matiere_index', methods: ['GET'])]
    public function index(
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        $sessionMatieres = $this->sessionMatiereRepo->findBySession($session);
        $stats = $this->sessionMatiereRepo->getStatistiquesSession($session);

        return $this->render('admin/session_matieres/index.html.twig', [
            'session' => $session,
            'sessionMatieres' => $sessionMatieres,
            'stats' => $stats,
        ]);
    }

    /**
     * Initialiser les matières depuis le référentiel de la formation
     */
    #[Route('/init', name: 'admin_session_matiere_init', methods: ['POST'])]
    public function initFromFormation(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        if ($this->isCsrfTokenValid('init' . $session->getId(), $request->request->get('_token'))) {
            $count = $session->initMatieresFromFormation();
            $this->em->flush();

            if ($count > 0) {
                $this->addFlash('success', sprintf('%d matière(s) initialisée(s) depuis le référentiel.', $count));
            } else {
                $this->addFlash('info', 'Aucune nouvelle matière à initialiser.');
            }
        }

        return $this->redirectToRoute('admin_session_matiere_index', [
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * Ajouter une matière hors référentiel à une session
     */
    #[Route('/add', name: 'admin_session_matiere_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        $sessionMatiere = new SessionMatiere();
        $sessionMatiere->setSession($session);
        $sessionMatiere->setOrdre($this->sessionMatiereRepo->getNextOrdre($session));

        // Récupérer les matières non encore dans la session
        $matieresDisponibles = $this->sessionMatiereRepo->findMatieresNotInSession($session);

        $form = $this->createForm(SessionMatiereType::class, $sessionMatiere, [
            'matieres_disponibles' => $matieresDisponibles,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($sessionMatiere);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Matière "%s" ajoutée à la session.',
                $sessionMatiere->getMatiere()->getCode()
            ));
            return $this->redirectToRoute('admin_session_matiere_index', [
                'sessionId' => $session->getId(),
            ]);
        }

        return $this->render('admin/session_matieres/form.html.twig', [
            'form' => $form,
            'session' => $session,
            'sessionMatiere' => $sessionMatiere,
            'title' => 'Ajouter une matière',
            'matieresDisponibles' => $matieresDisponibles,
        ]);
    }

    /**
     * Modifier une matière de session (volumes, statut)
     */
    #[Route('/{id}/edit', name: 'admin_session_matiere_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        SessionMatiere $sessionMatiere
    ): Response {
        // Vérifier que la matière appartient bien à cette session
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }

        $form = $this->createForm(SessionMatiereType::class, $sessionMatiere, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Matière "%s" modifiée.',
                $sessionMatiere->getMatiere()->getCode()
            ));
            return $this->redirectToRoute('admin_session_matiere_index', [
                'sessionId' => $session->getId(),
            ]);
        }

        return $this->render('admin/session_matieres/form.html.twig', [
            'form' => $form,
            'session' => $session,
            'sessionMatiere' => $sessionMatiere,
            'title' => 'Modifier la matière',
        ]);
    }

    /**
     * Activer/Désactiver une matière de session
     */
    #[Route('/{id}/toggle', name: 'admin_session_matiere_toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        SessionMatiere $sessionMatiere
    ): Response {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }

        if ($this->isCsrfTokenValid('toggle' . $sessionMatiere->getId(), $request->request->get('_token'))) {
            $sessionMatiere->setActif(!$sessionMatiere->isActif());
            $this->em->flush();

            $status = $sessionMatiere->isActif() ? 'activée' : 'désactivée';
            $this->addFlash('success', sprintf('Matière "%s" %s.', $sessionMatiere->getMatiere()->getCode(), $status));
        }

        return $this->redirectToRoute('admin_session_matiere_index', [
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * Supprimer une matière de session
     */
    #[Route('/{id}/delete', name: 'admin_session_matiere_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        SessionMatiere $sessionMatiere
    ): Response {
        if ($sessionMatiere->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette session.');
        }

        if ($this->isCsrfTokenValid('delete' . $sessionMatiere->getId(), $request->request->get('_token'))) {
            $code = $sessionMatiere->getMatiere()->getCode();
            $this->em->remove($sessionMatiere);
            $this->em->flush();

            $this->addFlash('success', sprintf('Matière "%s" retirée de la session.', $code));
        }

        return $this->redirectToRoute('admin_session_matiere_index', [
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * Mise à jour en masse des volumes (formulaire inline)
     */
    #[Route('/update-volumes', name: 'admin_session_matiere_update_volumes', methods: ['POST'])]
    public function updateVolumes(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        if ($this->isCsrfTokenValid('update_volumes' . $session->getId(), $request->request->get('_token'))) {
            $data = $request->request->all('volumes');
            
            foreach ($data as $id => $volumes) {
                $sessionMatiere = $this->sessionMatiereRepo->find($id);
                if ($sessionMatiere && $sessionMatiere->getSession()->getId() === $session->getId()) {
                    if (isset($volumes['planifie']) && $volumes['planifie'] !== '') {
                        $sessionMatiere->setVolumeHeuresPlanifie((int) $volumes['planifie']);
                    }
                    if (isset($volumes['realise']) && $volumes['realise'] !== '') {
                        $sessionMatiere->setVolumeHeuresRealise((int) $volumes['realise']);
                    }
                }
            }
            
            $this->em->flush();
            $this->addFlash('success', 'Volumes horaires mis à jour.');
        }

        return $this->redirectToRoute('admin_session_matiere_index', [
            'sessionId' => $session->getId(),
        ]);
    }
}
