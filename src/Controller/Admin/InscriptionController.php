<?php

namespace App\Controller\Admin;

use App\Entity\Inscription;
use App\Entity\Session;
use App\Entity\User;
use App\Form\InscriptionType;
use App\Form\InscriptionRapideType;
use App\Repository\InscriptionRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des inscriptions d'une session
 */
#[Route('/admin/sessions/{sessionId}/inscriptions', requirements: ['sessionId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
class InscriptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private InscriptionRepository $inscriptionRepo,
        private SessionRepository $sessionRepo,
        private UserRepository $userRepo,
        private RoleRepository $roleRepo
    ) {}

    /**
     * Liste des inscriptions d'une session
     */
    #[Route('', name: 'admin_inscription_index', methods: ['GET'])]
    public function index(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        $statut = $request->query->get('statut');
        $recherche = $request->query->get('q');
        
        $inscriptions = $this->inscriptionRepo->findBySessionWithFilters(
            session: $session,
            statut: $statut,
            recherche: $recherche
        );
        
        $stats = $this->inscriptionRepo->countByStatutForSession($session);
        
        return $this->render('admin/inscriptions/index.html.twig', [
            'session' => $session,
            'inscriptions' => $inscriptions,
            'stats' => $stats,
            'statuts' => Inscription::STATUTS,
            'filtreStatut' => $statut,
            'recherche' => $recherche,
        ]);
    }

    /**
     * Inscription rapide - Sélection d'un apprenti existant
     */
    #[Route('/ajouter', name: 'admin_inscription_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        // Récupérer les apprentis disponibles (pas déjà inscrits)
        $apprentisInscrits = [];
        foreach ($session->getInscriptions() as $insc) {
            $apprentisInscrits[] = $insc->getUser()->getId();
        }
        
        // Récupérer tous les apprentis actifs
        $roleApprenant = $this->roleRepo->findOneBy(['code' => 'ROLE_APPRENANT']);
        $tousApprentis = $roleApprenant 
            ? $this->userRepo->findByRoleCode('ROLE_APPRENANT')
            : [];
        
        // Filtrer ceux déjà inscrits
        $apprentisDisponibles = array_filter(
            $tousApprentis,
            fn(User $u) => !in_array($u->getId(), $apprentisInscrits)
        );
        
        if ($request->isMethod('POST')) {
            $userIds = $request->request->all('apprentis') ?? [];
            $option = $request->request->get('option');
            $statut = $request->request->get('statut', Inscription::STATUT_VALIDEE);
            
            if (empty($userIds)) {
                $this->addFlash('error', 'Veuillez sélectionner au moins un apprenti.');
                return $this->redirectToRoute('admin_inscription_ajouter', ['sessionId' => $session->getId()]);
            }
            
            $count = 0;
            foreach ($userIds as $userId) {
                $user = $this->userRepo->find($userId);
                if ($user && !$this->inscriptionRepo->isUserInscrit($user, $session)) {
                    $inscription = new Inscription();
                    $inscription->setUser($user);
                    $inscription->setSession($session);
                    $inscription->setStatut($statut);
                    $inscription->setOption($option ?: null);
                    $inscription->setCreatedBy($this->getUser());
                    
                    // Si validée directement, définir la date de début effective
                    if ($statut === Inscription::STATUT_VALIDEE) {
                        $inscription->setDateDebutEffective($session->getDateDebut());
                    }
                    
                    $this->em->persist($inscription);
                    $count++;
                }
            }
            
            $this->em->flush();
            
            $this->addFlash('success', sprintf('%d apprenti(s) inscrit(s) à la session.', $count));
            return $this->redirectToRoute('admin_inscription_index', ['sessionId' => $session->getId()]);
        }
        
        // Récupérer les options de la formation si disponibles
        $options = $session->getFormation()?->getOptions() ?? [];
        
        return $this->render('admin/inscriptions/ajouter.html.twig', [
            'session' => $session,
            'apprentis' => $apprentisDisponibles,
            'options' => $options,
            'statuts' => [
                Inscription::STATUT_EN_ATTENTE => Inscription::STATUTS[Inscription::STATUT_EN_ATTENTE],
                Inscription::STATUT_VALIDEE => Inscription::STATUTS[Inscription::STATUT_VALIDEE],
            ],
        ]);
    }

    /**
     * Modification d'une inscription
     */
    #[Route('/{id}/edit', name: 'admin_inscription_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        Inscription $inscription
    ): Response {
        // Vérifier que l'inscription appartient bien à cette session
        if ($inscription->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Inscription non trouvée pour cette session.');
        }
        
        $form = $this->createForm(InscriptionType::class, $inscription, [
            'formation_options' => $session->getFormation()?->getOptions() ?? [],
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'Inscription de "%s" modifiée.',
                $inscription->getUser()->getNomComplet()
            ));
            
            return $this->redirectToRoute('admin_inscription_index', ['sessionId' => $session->getId()]);
        }
        
        return $this->render('admin/inscriptions/edit.html.twig', [
            'session' => $session,
            'inscription' => $inscription,
            'form' => $form,
        ]);
    }

    /**
     * Changement rapide de statut
     */
    #[Route('/{id}/statut', name: 'admin_inscription_statut', methods: ['POST'])]
    public function changeStatut(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        Inscription $inscription
    ): Response {
        if ($inscription->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Inscription non trouvée pour cette session.');
        }
        
        if ($this->isCsrfTokenValid('statut' . $inscription->getId(), $request->request->get('_token'))) {
            $nouveauStatut = $request->request->get('statut');
            $motif = $request->request->get('motif');
            
            if (!array_key_exists($nouveauStatut, Inscription::STATUTS)) {
                $this->addFlash('error', 'Statut invalide.');
                return $this->redirectToRoute('admin_inscription_index', ['sessionId' => $session->getId()]);
            }
            
            $ancienStatut = $inscription->getStatut();
            
            switch ($nouveauStatut) {
                case Inscription::STATUT_VALIDEE:
                    $inscription->valider();
                    if (!$inscription->getDateDebutEffective()) {
                        $inscription->setDateDebutEffective($session->getDateDebut());
                    }
                    break;
                    
                case Inscription::STATUT_REFUSEE:
                    $inscription->refuser($motif ?? 'Non spécifié');
                    break;
                    
                case Inscription::STATUT_ANNULEE:
                    $inscription->annuler($motif ?? 'Non spécifié');
                    break;
                    
                case Inscription::STATUT_ABANDONNEE:
                    $inscription->abandonner($motif ?? 'Non spécifié');
                    break;
                    
                case Inscription::STATUT_TERMINEE:
                    $inscription->terminer();
                    break;
                    
                default:
                    $inscription->setStatut($nouveauStatut);
                    if ($motif) {
                        $inscription->setMotif($motif);
                    }
            }
            
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'Statut de "%s" modifié : %s → %s',
                $inscription->getUser()->getNomComplet(),
                Inscription::STATUTS[$ancienStatut],
                Inscription::STATUTS[$nouveauStatut]
            ));
        }
        
        return $this->redirectToRoute('admin_inscription_index', ['sessionId' => $session->getId()]);
    }

    /**
     * Validation en masse des inscriptions en attente
     */
    #[Route('/valider-masse', name: 'admin_inscription_valider_masse', methods: ['POST'])]
    public function validerMasse(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        if ($this->isCsrfTokenValid('valider_masse', $request->request->get('_token'))) {
            $ids = $request->request->all('inscriptions') ?? [];
            $count = 0;
            
            foreach ($ids as $id) {
                $inscription = $this->inscriptionRepo->find($id);
                if ($inscription 
                    && $inscription->getSession()->getId() === $session->getId()
                    && $inscription->getStatut() === Inscription::STATUT_EN_ATTENTE
                ) {
                    $inscription->valider();
                    $inscription->setDateDebutEffective($session->getDateDebut());
                    $count++;
                }
            }
            
            $this->em->flush();
            
            $this->addFlash('success', sprintf('%d inscription(s) validée(s).', $count));
        }
        
        return $this->redirectToRoute('admin_inscription_index', ['sessionId' => $session->getId()]);
    }

    /**
     * Suppression d'une inscription
     */
    #[Route('/{id}/delete', name: 'admin_inscription_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'sessionId')] Session $session,
        Inscription $inscription
    ): Response {
        if ($inscription->getSession()->getId() !== $session->getId()) {
            throw $this->createNotFoundException('Inscription non trouvée pour cette session.');
        }
        
        if ($this->isCsrfTokenValid('delete' . $inscription->getId(), $request->request->get('_token'))) {
            $nomComplet = $inscription->getUser()->getNomComplet();
            $this->em->remove($inscription);
            $this->em->flush();
            
            $this->addFlash('success', sprintf('Inscription de "%s" supprimée.', $nomComplet));
        }
        
        return $this->redirectToRoute('admin_inscription_index', ['sessionId' => $session->getId()]);
    }

    /**
     * Export de la liste des inscrits (pour impression)
     */
    #[Route('/export', name: 'admin_inscription_export', methods: ['GET'])]
    public function export(
        #[MapEntity(id: 'sessionId')] Session $session
    ): Response {
        $inscriptions = $this->inscriptionRepo->findBySessionWithFilters(
            session: $session,
            statut: Inscription::STATUT_VALIDEE
        );
        
        return $this->render('admin/inscriptions/export.html.twig', [
            'session' => $session,
            'inscriptions' => $inscriptions,
        ]);
    }
}
