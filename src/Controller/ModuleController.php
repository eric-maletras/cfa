<?php

namespace App\Controller;

use App\Repository\ModuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ModuleController extends AbstractController
{
    /**
     * Mapping des routes de modules vers les routes de contrôleurs dédiés
     * Quand un module utilise une de ces routes, on redirige vers le contrôleur dédié
     */
    private const ROUTE_MAPPINGS = [
        'admin_formations' => 'admin_formation_index',
        'admin_promotions' => 'admin_session_index',
        'admin_users' => 'admin_user_index',
        'admin_absences' => 'admin_absence_index',
        'admin_evaluations' => 'admin_evaluation_index',
        // Ajouter d'autres mappings au fur et à mesure du développement
    ];

    public function __construct(
        private RouterInterface $router
    ) {}

    #[Route('/module/{slug}', name: 'app_module')]
    public function index(string $slug, ModuleRepository $moduleRepository): Response
    {
        // Chercher le module par sa route
        $module = $moduleRepository->findOneBy(['route' => $slug, 'actif' => true]);
        
        if (!$module) {
            throw $this->createNotFoundException('Module non trouvé');
        }
        
        // Vérifier que l'utilisateur a accès à ce module
        $user = $this->getUser();
        $userRoleIds = [];
        foreach ($user->getRolesEntities() as $role) {
            $userRoleIds[] = $role->getId();
        }
        
        $moduleRoleIds = [];
        foreach ($module->getRoles() as $role) {
            $moduleRoleIds[] = $role->getId();
        }
        
        if (empty(array_intersect($userRoleIds, $moduleRoleIds))) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce module');
        }
        
        // Vérifier si une route dédiée existe pour ce module
        if (isset(self::ROUTE_MAPPINGS[$slug])) {
            $targetRoute = self::ROUTE_MAPPINGS[$slug];
            // Vérifier que la route existe
            try {
                $this->router->generate($targetRoute);
                return $this->redirectToRoute($targetRoute);
            } catch (\Exception $e) {
                // La route n'existe pas encore, on continue vers le template
            }
        }
        
        // Essayer de rendre le template du module
        $template = 'modules/' . str_replace('app_', '', $slug) . '.html.twig';
        
        if (!$this->templateExists($template)) {
            return $this->render('modules/placeholder.html.twig', [
                'module' => $module,
            ]);
        }
        
        return $this->render($template, [
            'module' => $module,
        ]);
    }
    
    private function templateExists(string $template): bool
    {
        return file_exists(__DIR__ . '/../../templates/' . $template);
    }
}
