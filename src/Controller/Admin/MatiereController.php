<?php

namespace App\Controller\Admin;

use App\Entity\Matiere;
use App\Form\MatiereType;
use App\Repository\MatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des matières (référentiel)
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_ADMIN
 */
#[Route('/admin/matieres')]
#[IsGranted('ROLE_ADMIN')]
class MatiereController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MatiereRepository $matiereRepo
    ) {}

    /**
     * Liste des matières avec comptage des formations
     */
    #[Route('', name: 'admin_matiere_index', methods: ['GET'])]
    public function index(): Response
    {
        $matieres = $this->matiereRepo->findBy([], ['code' => 'ASC']);

        return $this->render('admin/matieres/index.html.twig', [
            'matieres' => $matieres,
        ]);
    }

    /**
     * Création d'une nouvelle matière
     */
    #[Route('/new', name: 'admin_matiere_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $matiere = new Matiere();
        $form = $this->createForm(MatiereType::class, $matiere);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($matiere);
            $this->em->flush();

            $this->addFlash('success', sprintf('Matière "%s" créée avec succès.', $matiere->getCode()));
            return $this->redirectToRoute('admin_matiere_index');
        }

        return $this->render('admin/matieres/form.html.twig', [
            'form' => $form,
            'matiere' => $matiere,
            'title' => 'Nouvelle matière',
        ]);
    }

    /**
     * Modification d'une matière
     */
    #[Route('/{id}/edit', name: 'admin_matiere_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Matiere $matiere): Response
    {
        $form = $this->createForm(MatiereType::class, $matiere);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', sprintf('Matière "%s" modifiée avec succès.', $matiere->getCode()));
            return $this->redirectToRoute('admin_matiere_index');
        }

        return $this->render('admin/matieres/form.html.twig', [
            'form' => $form,
            'matiere' => $matiere,
            'title' => 'Modifier la matière',
        ]);
    }

    /**
     * Affiche le détail d'une matière avec ses formations
     */
    #[Route('/{id}', name: 'admin_matiere_show', methods: ['GET'])]
    public function show(Matiere $matiere): Response
    {
        return $this->render('admin/matieres/show.html.twig', [
            'matiere' => $matiere,
        ]);
    }

    /**
     * Suppression d'une matière
     */
    #[Route('/{id}/delete', name: 'admin_matiere_delete', methods: ['POST'])]
    public function delete(Request $request, Matiere $matiere): Response
    {
        if ($this->isCsrfTokenValid('delete' . $matiere->getId(), $request->request->get('_token'))) {
            // Vérifier qu'aucune formation n'utilise cette matière
            if ($matiere->getNombreFormations() > 0) {
                $this->addFlash('error', sprintf(
                    'Impossible de supprimer la matière "%s" : elle est utilisée par %d formation(s).',
                    $matiere->getCode(),
                    $matiere->getNombreFormations()
                ));
            } else {
                $code = $matiere->getCode();
                $this->em->remove($matiere);
                $this->em->flush();
                $this->addFlash('success', sprintf('Matière "%s" supprimée.', $code));
            }
        }

        return $this->redirectToRoute('admin_matiere_index');
    }

    /**
     * Activer/Désactiver une matière
     */
    #[Route('/{id}/toggle', name: 'admin_matiere_toggle', methods: ['POST'])]
    public function toggle(Request $request, Matiere $matiere): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $matiere->getId(), $request->request->get('_token'))) {
            $matiere->setActif(!$matiere->isActif());
            $this->em->flush();

            $status = $matiere->isActif() ? 'activée' : 'désactivée';
            $this->addFlash('success', sprintf('Matière "%s" %s.', $matiere->getCode(), $status));
        }

        return $this->redirectToRoute('admin_matiere_index');
    }
}
