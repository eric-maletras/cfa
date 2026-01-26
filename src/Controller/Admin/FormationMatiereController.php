<?php

namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Entity\FormationMatiere;
use App\Form\FormationMatiereType;
use App\Repository\FormationMatiereRepository;
use App\Repository\MatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des matières d'une formation (liaison FormationMatiere)
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_ADMIN
 */
#[Route('/admin/formations/{formationId}/matieres')]
#[IsGranted('ROLE_ADMIN')]
class FormationMatiereController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationMatiereRepository $formationMatiereRepo,
        private MatiereRepository $matiereRepo
    ) {}

    /**
     * Liste des matières d'une formation
     */
    #[Route('', name: 'admin_formation_matiere_index', methods: ['GET'])]
    public function index(
        #[MapEntity(id: 'formationId')] Formation $formation
    ): Response
    {
        $formationMatieres = $this->formationMatiereRepo->findByFormation($formation);
        $stats = $this->formationMatiereRepo->getStatistiquesFormation($formation);

        return $this->render('admin/matieres/formation_matieres.html.twig', [
            'formation' => $formation,
            'formationMatieres' => $formationMatieres,
            'stats' => $stats,
        ]);
    }

    /**
     * Ajouter une matière à une formation
     */
    #[Route('/add', name: 'admin_formation_matiere_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        #[MapEntity(id: 'formationId')] Formation $formation
    ): Response
    {
        $formationMatiere = new FormationMatiere();
        $formationMatiere->setFormation($formation);
        
        // Récupérer le prochain ordre
        $formationMatiere->setOrdre($this->formationMatiereRepo->getNextOrdre($formation));

        // Récupérer les matières disponibles (non encore dans la formation)
        $matieresDisponibles = $this->matiereRepo->findNotInFormation($formation->getId());

        $form = $this->createForm(FormationMatiereType::class, $formationMatiere, [
            'matieres_disponibles' => $matieresDisponibles,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($formationMatiere);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Matière "%s" ajoutée à la formation "%s".',
                $formationMatiere->getMatiere()->getCode(),
                $formation->getIntituleCourt()
            ));
            return $this->redirectToRoute('admin_formation_matiere_index', [
                'formationId' => $formation->getId(),
            ]);
        }

        return $this->render('admin/matieres/formation_matiere_form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'formationMatiere' => $formationMatiere,
            'title' => 'Ajouter une matière',
            'matieresDisponibles' => $matieresDisponibles,
        ]);
    }

    /**
     * Modifier une liaison formation-matière
     */
    #[Route('/{id}/edit', name: 'admin_formation_matiere_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'formationId')] Formation $formation,
        FormationMatiere $formationMatiere
    ): Response
    {
        // Vérifier que la liaison appartient bien à cette formation
        if ($formationMatiere->getFormation()->getId() !== $formation->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette formation.');
        }

        $form = $this->createForm(FormationMatiereType::class, $formationMatiere, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Matière "%s" modifiée.',
                $formationMatiere->getMatiere()->getCode()
            ));
            return $this->redirectToRoute('admin_formation_matiere_index', [
                'formationId' => $formation->getId(),
            ]);
        }

        return $this->render('admin/matieres/formation_matiere_form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'formationMatiere' => $formationMatiere,
            'title' => 'Modifier la matière',
        ]);
    }

    /**
     * Supprimer une matière d'une formation
     */
    #[Route('/{id}/delete', name: 'admin_formation_matiere_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'formationId')] Formation $formation,
        FormationMatiere $formationMatiere
    ): Response
    {
        // Vérifier que la liaison appartient bien à cette formation
        if ($formationMatiere->getFormation()->getId() !== $formation->getId()) {
            throw $this->createNotFoundException('Cette matière n\'appartient pas à cette formation.');
        }

        if ($this->isCsrfTokenValid('delete' . $formationMatiere->getId(), $request->request->get('_token'))) {
            $code = $formationMatiere->getMatiere()->getCode();
            $this->em->remove($formationMatiere);
            $this->em->flush();

            $this->addFlash('success', sprintf('Matière "%s" retirée de la formation.', $code));
        }

        return $this->redirectToRoute('admin_formation_matiere_index', [
            'formationId' => $formation->getId(),
        ]);
    }

    /**
     * Réordonner les matières (via AJAX potentiellement)
     */
    #[Route('/reorder', name: 'admin_formation_matiere_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        #[MapEntity(id: 'formationId')] Formation $formation
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['order']) && is_array($data['order'])) {
            foreach ($data['order'] as $index => $id) {
                $formationMatiere = $this->formationMatiereRepo->find($id);
                if ($formationMatiere && $formationMatiere->getFormation()->getId() === $formation->getId()) {
                    $formationMatiere->setOrdre($index);
                }
            }
            $this->em->flush();

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'error' => 'Invalid data'], 400);
    }
}
