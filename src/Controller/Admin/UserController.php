<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Session;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\SessionRepository;
use App\Repository\DevoirRepository;
use App\Repository\NoteRepository;
use App\Repository\InscriptionRepository;
use App\Service\PasswordGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des utilisateurs
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_ADMIN
 */
#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private RoleRepository $roleRepo,
        private SessionRepository $sessionRepo,
        private DevoirRepository $devoirRepo,
        private NoteRepository $noteRepo,
        private InscriptionRepository $inscriptionRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordGeneratorService $passwordGenerator
    ) {}

    /**
     * Liste des utilisateurs avec filtres
     */
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupération des paramètres de filtre
        $roleFilter = $request->query->get('role');
        $statutFilter = $request->query->get('statut');
        $recherche = $request->query->get('q');
        
        // Construction de la requête avec filtres
        $users = $this->userRepo->findWithFilters(
            roleId: $roleFilter ? (int) $roleFilter : null,
            actif: $statutFilter !== null && $statutFilter !== '' ? ($statutFilter === '1') : null,
            recherche: $recherche
        );
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'roles' => $this->roleRepo->findBy([], ['libelle' => 'ASC']),
            'filtreRole' => $roleFilter,
            'filtreStatut' => $statutFilter,
            'recherche' => $recherche,
        ]);
    }

    /**
     * Création d'un nouvel utilisateur
     * Le mot de passe est généré automatiquement
     */
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_new' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération automatique du mot de passe temporaire
            $tempPassword = $this->passwordGenerator->generate();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);
            $user->setMustChangePassword(true);
            
            $this->em->persist($user);
            $this->em->flush();
            
            // TODO: Envoyer le mail avec le mot de passe temporaire
            // Pour l'instant, on affiche le mot de passe dans un message flash
            $this->addFlash('success', sprintf(
                'Utilisateur "%s" créé avec succès.',
                $user->getNomComplet()
            ));
            $this->addFlash('warning', sprintf(
                '🔑 Mot de passe temporaire : %s (à communiquer à l\'utilisateur ou envoi par mail à implémenter)',
                $tempPassword
            ));
            
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/users/form.html.twig', [
            'form' => $form,
            'user' => $user,
            'title' => 'Nouvel utilisateur',
        ]);
    }

    /**
     * Affichage détaillé d'un utilisateur
     * Redirige vers la vue formateur enrichie si l'utilisateur est formateur
     */
    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        // Vérifie si l'utilisateur est un formateur
        $isFormateur = false;
        foreach ($user->getRolesEntities() as $role) {
            if ($role->getCode() === 'ROLE_FORMATEUR') {
                $isFormateur = true;
                break;
            }
        }
        
        // Si formateur, utilise la vue enrichie
        if ($isFormateur) {
            return $this->showFormateur($user);
        }
        
        // Vue standard pour les autres utilisateurs
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Affichage enrichi d'un formateur avec ses sessions, devoirs et apprenants
     */
    private function showFormateur(User $user): Response
    {
        // Récupère les sessions où le formateur intervient ou est responsable
        $sessionsResponsable = $this->sessionRepo->findByResponsable($user);
        $sessionsFormateur = $this->sessionRepo->findByFormateur($user);
        
        // Fusionner et dédoublonner les sessions
        $sessionsMap = [];
        foreach ($sessionsResponsable as $session) {
            $sessionsMap[$session->getId()] = $session;
        }
        foreach ($sessionsFormateur as $session) {
            $sessionsMap[$session->getId()] = $session;
        }
        $sessions = array_values($sessionsMap);
        
        // Trie par date de début décroissante
        usort($sessions, fn($a, $b) => $b->getDateDebut() <=> $a->getDateDebut());
        
        // Récupère les devoirs récents du formateur
        $devoirs = $this->devoirRepo->findRecentByFormateur($user, 10);
        
        // Récupère la liste des apprenants via les inscriptions aux sessions
        $apprenants = $this->getApprenantsWithMoyennes($sessions);
        
        return $this->render('admin/users/show_formateur.html.twig', [
            'user' => $user,
            'sessions' => $sessions,
            'devoirs' => $devoirs,
            'apprenants' => $apprenants,
        ]);
    }

    /**
     * Récupère les apprenants de toutes les sessions avec leurs moyennes
     * 
     * @param Session[] $sessions
     * @return array<int, array{user: User, session: Session, moyenne: float|null}>
     */
    private function getApprenantsWithMoyennes(array $sessions): array
    {
        $apprenants = [];
        $seen = []; // Pour éviter les doublons
        
        foreach ($sessions as $session) {
            // Récupère les inscriptions validées de cette session
            $inscriptions = $this->inscriptionRepo->findBySessionWithFilters($session, 'validee');
            
            foreach ($inscriptions as $inscription) {
                $apprenant = $inscription->getUser();
                
                // Évite les doublons (même apprenant dans plusieurs sessions)
                $key = $apprenant->getId() . '_' . $session->getId();
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                
                // Calcule la moyenne de l'apprenant pour cette session
                $moyenne = $this->noteRepo->calculateMoyenneApprenant($apprenant, $session);
                
                $apprenants[] = [
                    'user' => $apprenant,
                    'session' => $session,
                    'moyenne' => $moyenne,
                ];
            }
        }
        
        // Trie par nom/prénom
        usort($apprenants, function($a, $b) {
            $cmp = $a['user']->getNom() <=> $b['user']->getNom();
            if ($cmp === 0) {
                return $a['user']->getPrenom() <=> $b['user']->getPrenom();
            }
            return $cmp;
        });
        
        return $apprenants;
    }

    /**
     * Modification d'un utilisateur
     */
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_new' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du nouveau mot de passe si fourni
            if ($form->has('plainPassword')) {
                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                    // Si l'admin change le mot de passe manuellement, on désactive le flag
                    $user->setMustChangePassword(false);
                }
            }
            
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'Utilisateur "%s" modifié avec succès.',
                $user->getNomComplet()
            ));
            
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/users/form.html.twig', [
            'form' => $form,
            'user' => $user,
            'title' => 'Modifier l\'utilisateur',
        ]);
    }

    /**
     * Suppression d'un utilisateur
     */
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $nomComplet = $user->getNomComplet();
            $this->em->remove($user);
            $this->em->flush();
            
            $this->addFlash('success', sprintf('Utilisateur "%s" supprimé.', $nomComplet));
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Activation/désactivation d'un utilisateur
     */
    #[Route('/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggle(Request $request, User $user): Response
    {
        // Empêcher la désactivation de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            $user->setActif(!$user->isActif());
            $this->em->flush();
            
            $statut = $user->isActif() ? 'activé' : 'désactivé';
            $this->addFlash('success', sprintf(
                'Utilisateur "%s" %s.',
                $user->getNomComplet(),
                $statut
            ));
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Gestion des rôles d'un utilisateur (page dédiée)
     */
    #[Route('/{id}/roles', name: 'admin_user_roles', methods: ['GET', 'POST'])]
    public function roles(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('roles' . $user->getId(), $request->request->get('_token'))) {
                // Récupérer les rôles sélectionnés
                $roleIds = $request->request->all('roles') ?? [];
                
                // Supprimer tous les rôles actuels
                foreach ($user->getRolesEntities() as $role) {
                    $user->removeRolesEntity($role);
                }
                
                // Ajouter les nouveaux rôles
                foreach ($roleIds as $roleId) {
                    $role = $this->roleRepo->find($roleId);
                    if ($role) {
                        $user->addRolesEntity($role);
                    }
                }
                
                $this->em->flush();
                
                $this->addFlash('success', sprintf(
                    'Rôles de "%s" mis à jour.',
                    $user->getNomComplet()
                ));
                
                return $this->redirectToRoute('admin_user_index');
            }
        }
        
        // Récupérer les IDs des rôles actuels de l'utilisateur
        $userRoleIds = [];
        foreach ($user->getRolesEntities() as $role) {
            $userRoleIds[] = $role->getId();
        }
        
        return $this->render('admin/users/roles.html.twig', [
            'user' => $user,
            'roles' => $this->roleRepo->findBy([], ['libelle' => 'ASC']),
            'userRoleIds' => $userRoleIds,
        ]);
    }

    /**
     * Réinitialisation du mot de passe (génère un nouveau mot de passe temporaire)
     */
    #[Route('/{id}/reset-password', name: 'admin_user_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('reset' . $user->getId(), $request->request->get('_token'))) {
            // Générer un mot de passe temporaire
            $tempPassword = $this->passwordGenerator->generate();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);
            $user->setMustChangePassword(true);
            
            $this->em->flush();
            
            // TODO: Envoyer le mail avec le mot de passe temporaire
            $this->addFlash('warning', sprintf(
                '🔑 Nouveau mot de passe temporaire pour "%s" : %s (à communiquer à l\'utilisateur)',
                $user->getNomComplet(),
                $tempPassword
            ));
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
}
