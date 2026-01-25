<?php

namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Entity\NiveauQualification;
use App\Entity\TypeCertification;
use App\Entity\CodeNSF;
use App\Entity\CodeROME;
use App\Form\FormationType;
use App\Form\NiveauQualificationType;
use App\Form\TypeCertificationType;
use App\Form\CodeNSFType;
use App\Form\CodeROMEType;
use App\Repository\FormationRepository;
use App\Repository\NiveauQualificationRepository;
use App\Repository\TypeCertificationRepository;
use App\Repository\CodeNSFRepository;
use App\Repository\CodeROMERepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des formations et tables de référence
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_ADMIN
 */
#[Route('/admin/formations')]
#[IsGranted('ROLE_ADMIN')]
class FormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepo,
        private NiveauQualificationRepository $niveauRepo,
        private TypeCertificationRepository $typeRepo,
        private CodeNSFRepository $nsfRepo,
        private CodeROMERepository $romeRepo
    ) {}

    /**
     * Page principale avec tous les onglets
     */
    #[Route('', name: 'admin_formation_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tab = $request->query->get('tab', 'formations');
        
        return $this->render('admin/formations/index.html.twig', [
            'formations' => $this->formationRepo->findBy([], ['intitule' => 'ASC']),
            'niveaux' => $this->niveauRepo->findBy([], ['code' => 'ASC']),
            'types' => $this->typeRepo->findBy([], ['ordreAffichage' => 'ASC', 'libelle' => 'ASC']),
            'codesNsf' => $this->nsfRepo->findBy(['niveau' => 3], ['code' => 'ASC']), // Niveau 3 = groupes
            'codesRome' => $this->romeRepo->findBy([], ['code' => 'ASC']),
            'activeTab' => $tab,
        ]);
    }

    // =========================================================================
    // FORMATIONS
    // =========================================================================

    #[Route('/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    public function newFormation(Request $request): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($formation);
            $this->em->flush();
            
            $this->addFlash('success', 'Formation créée avec succès.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'formations']);
        }

        return $this->render('admin/formations/form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'title' => 'Nouvelle formation',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_formation_edit', methods: ['GET', 'POST'])]
    public function editFormation(Request $request, Formation $formation): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', 'Formation modifiée avec succès.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'formations']);
        }

        return $this->render('admin/formations/form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'title' => 'Modifier la formation',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_formation_delete', methods: ['POST'])]
    public function deleteFormation(Request $request, Formation $formation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $request->request->get('_token'))) {
            $this->em->remove($formation);
            $this->em->flush();
            $this->addFlash('success', 'Formation supprimée.');
        }

        return $this->redirectToRoute('admin_formation_index', ['tab' => 'formations']);
    }

    #[Route('/{id}/toggle', name: 'admin_formation_toggle', methods: ['POST'])]
    public function toggleFormation(Request $request, Formation $formation): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$formation->getId(), $request->request->get('_token'))) {
            $formation->setActif(!$formation->isActif());
            $this->em->flush();
            $this->addFlash('success', 'Statut de la formation mis à jour.');
        }

        return $this->redirectToRoute('admin_formation_index', ['tab' => 'formations']);
    }

    // =========================================================================
    // NIVEAUX DE QUALIFICATION
    // =========================================================================

    #[Route('/niveau/new', name: 'admin_niveau_new', methods: ['GET', 'POST'])]
    public function newNiveau(Request $request): Response
    {
        $niveau = new NiveauQualification();
        $form = $this->createForm(NiveauQualificationType::class, $niveau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($niveau);
            $this->em->flush();
            
            $this->addFlash('success', 'Niveau de qualification créé.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'niveaux']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Nouveau niveau de qualification',
            'backTab' => 'niveaux',
        ]);
    }

    #[Route('/niveau/{id}/edit', name: 'admin_niveau_edit', methods: ['GET', 'POST'])]
    public function editNiveau(Request $request, NiveauQualification $niveau): Response
    {
        $form = $this->createForm(NiveauQualificationType::class, $niveau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', 'Niveau de qualification modifié.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'niveaux']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Modifier le niveau de qualification',
            'backTab' => 'niveaux',
        ]);
    }

    #[Route('/niveau/{id}/delete', name: 'admin_niveau_delete', methods: ['POST'])]
    public function deleteNiveau(Request $request, NiveauQualification $niveau): Response
    {
        if ($this->isCsrfTokenValid('delete'.$niveau->getId(), $request->request->get('_token'))) {
            // Vérifier qu'aucune formation n'utilise ce niveau
            if ($niveau->getFormations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer : ce niveau est utilisé par des formations.');
            } else {
                $this->em->remove($niveau);
                $this->em->flush();
                $this->addFlash('success', 'Niveau supprimé.');
            }
        }

        return $this->redirectToRoute('admin_formation_index', ['tab' => 'niveaux']);
    }

    // =========================================================================
    // TYPES DE CERTIFICATION
    // =========================================================================

    #[Route('/type/new', name: 'admin_type_new', methods: ['GET', 'POST'])]
    public function newType(Request $request): Response
    {
        $type = new TypeCertification();
        $form = $this->createForm(TypeCertificationType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($type);
            $this->em->flush();
            
            $this->addFlash('success', 'Type de certification créé.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'types']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Nouveau type de certification',
            'backTab' => 'types',
        ]);
    }

    #[Route('/type/{id}/edit', name: 'admin_type_edit', methods: ['GET', 'POST'])]
    public function editType(Request $request, TypeCertification $type): Response
    {
        $form = $this->createForm(TypeCertificationType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', 'Type de certification modifié.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'types']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Modifier le type de certification',
            'backTab' => 'types',
        ]);
    }

    #[Route('/type/{id}/delete', name: 'admin_type_delete', methods: ['POST'])]
    public function deleteType(Request $request, TypeCertification $type): Response
    {
        if ($this->isCsrfTokenValid('delete'.$type->getId(), $request->request->get('_token'))) {
            if ($type->getFormations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer : ce type est utilisé par des formations.');
            } else {
                $this->em->remove($type);
                $this->em->flush();
                $this->addFlash('success', 'Type supprimé.');
            }
        }

        return $this->redirectToRoute('admin_formation_index', ['tab' => 'types']);
    }

    // =========================================================================
    // CODES NSF
    // =========================================================================

    #[Route('/nsf/new', name: 'admin_nsf_new', methods: ['GET', 'POST'])]
    public function newNsf(Request $request): Response
    {
        $nsf = new CodeNSF();
        $form = $this->createForm(CodeNSFType::class, $nsf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($nsf);
            $this->em->flush();
            
            $this->addFlash('success', 'Code NSF créé.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'nsf']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Nouveau code NSF',
            'backTab' => 'nsf',
        ]);
    }

    #[Route('/nsf/{id}/edit', name: 'admin_nsf_edit', methods: ['GET', 'POST'])]
    public function editNsf(Request $request, CodeNSF $nsf): Response
    {
        $form = $this->createForm(CodeNSFType::class, $nsf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', 'Code NSF modifié.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'nsf']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Modifier le code NSF',
            'backTab' => 'nsf',
        ]);
    }

    #[Route('/nsf/{id}/delete', name: 'admin_nsf_delete', methods: ['POST'])]
    public function deleteNsf(Request $request, CodeNSF $nsf): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nsf->getId(), $request->request->get('_token'))) {
            if ($nsf->getFormations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer : ce code NSF est utilisé par des formations.');
            } else {
                $this->em->remove($nsf);
                $this->em->flush();
                $this->addFlash('success', 'Code NSF supprimé.');
            }
        }

        return $this->redirectToRoute('admin_formation_index', ['tab' => 'nsf']);
    }

    // =========================================================================
    // CODES ROME
    // =========================================================================

    #[Route('/rome/new', name: 'admin_rome_new', methods: ['GET', 'POST'])]
    public function newRome(Request $request): Response
    {
        $rome = new CodeROME();
        $form = $this->createForm(CodeROMEType::class, $rome);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($rome);
            $this->em->flush();
            
            $this->addFlash('success', 'Code ROME créé.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'rome']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Nouveau code ROME',
            'backTab' => 'rome',
        ]);
    }

    #[Route('/rome/{id}/edit', name: 'admin_rome_edit', methods: ['GET', 'POST'])]
    public function editRome(Request $request, CodeROME $rome): Response
    {
        $form = $this->createForm(CodeROMEType::class, $rome);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', 'Code ROME modifié.');
            return $this->redirectToRoute('admin_formation_index', ['tab' => 'rome']);
        }

        return $this->render('admin/formations/form_simple.html.twig', [
            'form' => $form,
            'title' => 'Modifier le code ROME',
            'backTab' => 'rome',
        ]);
    }

    #[Route('/rome/{id}/delete', name: 'admin_rome_delete', methods: ['POST'])]
    public function deleteRome(Request $request, CodeROME $rome): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rome->getId(), $request->request->get('_token'))) {
            if ($rome->getFormations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer : ce code ROME est utilisé par des formations.');
            } else {
                $this->em->remove($rome);
                $this->em->flush();
                $this->addFlash('success', 'Code ROME supprimé.');
            }
        }

        return $this->redirectToRoute('admin_formation_index', ['tab' => 'rome']);
    }
}
