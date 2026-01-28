<?php

namespace App\Controller\Admin;

use App\Entity\Salle;
use App\Entity\TypeSalle;
use App\Form\SalleType;
use App\Repository\SalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur CRUD pour la gestion des salles
 * 
 * Accessible aux utilisateurs ayant le rôle ROLE_ADMIN
 */
#[Route('/admin/salles')]
#[IsGranted('ROLE_ADMIN')]
class SalleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SalleRepository $salleRepository,
    ) {
    }

    /**
     * Liste des salles avec filtres et recherche
     */
    #[Route('', name: 'admin_salle_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupération des filtres
        $search = $request->query->get('search');
        $typeFilter = $request->query->get('type');
        $actifFilter = $request->query->get('actif');

        // Conversion du filtre type
        $type = null;
        if ($typeFilter !== null && $typeFilter !== '') {
            $type = TypeSalle::tryFrom($typeFilter);
        }

        // Conversion du filtre actif
        $actif = null;
        if ($actifFilter !== null && $actifFilter !== '') {
            $actif = $actifFilter === '1';
        }

        // Recherche des salles
        $salles = $this->salleRepository->findByFilters($search, $type, $actif);

        // Statistiques par type
        $countByType = $this->salleRepository->countByType();

        return $this->render('admin/salle/index.html.twig', [
            'salles' => $salles,
            'types' => TypeSalle::cases(),
            'countByType' => $countByType,
            'filters' => [
                'search' => $search,
                'type' => $typeFilter,
                'actif' => $actifFilter,
            ],
        ]);
    }

    /**
     * Création d'une nouvelle salle
     */
    #[Route('/new', name: 'admin_salle_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $salle = new Salle();
        $salle->setActif(true);

        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($salle);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'La salle "%s" a été créée avec succès.',
                $salle->getCode()
            ));

            return $this->redirectToRoute('admin_salle_index');
        }

        return $this->render('admin/salle/new.html.twig', [
            'salle' => $salle,
            'form' => $form,
        ]);
    }

    /**
     * Affichage détaillé d'une salle
     */
    #[Route('/{id}', name: 'admin_salle_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Salle $salle): Response
    {
        return $this->render('admin/salle/show.html.twig', [
            'salle' => $salle,
        ]);
    }

    /**
     * Modification d'une salle existante
     */
    #[Route('/{id}/edit', name: 'admin_salle_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Salle $salle): Response
    {
        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'La salle "%s" a été modifiée avec succès.',
                $salle->getCode()
            ));

            return $this->redirectToRoute('admin_salle_index');
        }

        return $this->render('admin/salle/edit.html.twig', [
            'salle' => $salle,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'une salle
     */
    #[Route('/{id}/delete', name: 'admin_salle_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Salle $salle): Response
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete_salle_' . $salle->getId(), $token)) {
            $code = $salle->getCode();
            
            $this->entityManager->remove($salle);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'La salle "%s" a été supprimée.',
                $code
            ));
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('admin_salle_index');
    }

    /**
     * Basculement du statut actif d'une salle
     */
    #[Route('/{id}/toggle', name: 'admin_salle_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, Salle $salle): Response
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('toggle_salle_' . $salle->getId(), $token)) {
            $salle->setActif(!$salle->isActif());
            $this->entityManager->flush();

            $status = $salle->isActif() ? 'activée' : 'désactivée';
            $this->addFlash('success', sprintf(
                'La salle "%s" a été %s.',
                $salle->getCode(),
                $status
            ));
        }

        return $this->redirectToRoute('admin_salle_index');
    }
}
