<?php

namespace App\Controller\Admin;

use App\Entity\MotifAbsence;
use App\Form\MotifAbsenceType;
use App\Repository\MotifAbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration des motifs d'absence
 * 
 * CRUD complet pour gérer les motifs prédéfinis d'absence.
 */
#[Route('/admin/motifs-absence')]
#[IsGranted('ROLE_ADMIN')]
class MotifAbsenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MotifAbsenceRepository $motifRepo
    ) {}

    /**
     * Liste des motifs d'absence
     */
    #[Route('', name: 'admin_motif_absence_index', methods: ['GET'])]
    public function index(): Response
    {
        $motifs = $this->motifRepo->findAllOrdered();
        
        // Récupérer les stats d'utilisation
        $statsUtilisation = [];
        foreach ($motifs as $motif) {
            $statsUtilisation[$motif->getId()] = $this->motifRepo->countUtilisations($motif);
        }

        return $this->render('admin/motif_absence/index.html.twig', [
            'motifs' => $motifs,
            'statsUtilisation' => $statsUtilisation,
        ]);
    }

    /**
     * Création d'un nouveau motif
     */
    #[Route('/nouveau', name: 'admin_motif_absence_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $motif = new MotifAbsence();
        $motif->setActif(true);
        $motif->setOrdre(10);
        
        $form = $this->createForm(MotifAbsenceType::class, $motif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($motif);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Motif "%s" créé avec succès.',
                $motif->getLibelle()
            ));

            return $this->redirectToRoute('admin_motif_absence_index');
        }

        return $this->render('admin/motif_absence/new.html.twig', [
            'motif' => $motif,
            'form' => $form,
        ]);
    }

    /**
     * Modification d'un motif
     */
    #[Route('/{id}/modifier', name: 'admin_motif_absence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MotifAbsence $motif): Response
    {
        $form = $this->createForm(MotifAbsenceType::class, $motif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Motif "%s" modifié avec succès.',
                $motif->getLibelle()
            ));

            return $this->redirectToRoute('admin_motif_absence_index');
        }

        $nbUtilisations = $this->motifRepo->countUtilisations($motif);

        return $this->render('admin/motif_absence/edit.html.twig', [
            'motif' => $motif,
            'form' => $form,
            'nbUtilisations' => $nbUtilisations,
        ]);
    }

    /**
     * Suppression d'un motif
     */
    #[Route('/{id}/supprimer', name: 'admin_motif_absence_delete', methods: ['POST'])]
    public function delete(Request $request, MotifAbsence $motif): Response
    {
        if (!$this->isCsrfTokenValid('delete_motif_' . $motif->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_motif_absence_index');
        }

        // Vérifier si le motif est utilisé
        $nbUtilisations = $this->motifRepo->countUtilisations($motif);
        if ($nbUtilisations > 0) {
            $this->addFlash('error', sprintf(
                'Impossible de supprimer le motif "%s" car il est utilisé %d fois. Désactivez-le plutôt.',
                $motif->getLibelle(),
                $nbUtilisations
            ));
            return $this->redirectToRoute('admin_motif_absence_index');
        }

        $libelle = $motif->getLibelle();
        $this->em->remove($motif);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Motif "%s" supprimé avec succès.',
            $libelle
        ));

        return $this->redirectToRoute('admin_motif_absence_index');
    }

    /**
     * Basculer l'état actif/inactif
     */
    #[Route('/{id}/toggle', name: 'admin_motif_absence_toggle', methods: ['POST'])]
    public function toggle(Request $request, MotifAbsence $motif): Response
    {
        if (!$this->isCsrfTokenValid('toggle_motif_' . $motif->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_motif_absence_index');
        }

        $motif->setActif(!$motif->isActif());
        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Motif "%s" %s.',
            $motif->getLibelle(),
            $motif->isActif() ? 'activé' : 'désactivé'
        ));

        return $this->redirectToRoute('admin_motif_absence_index');
    }
}
