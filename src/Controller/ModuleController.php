<?php

namespace App\Controller;

use App\Repository\ModuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ModuleController extends AbstractController
{
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
        
        // Rendre le template du module (ou placeholder si n'existe pas)
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
