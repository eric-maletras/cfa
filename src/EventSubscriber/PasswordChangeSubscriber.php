<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PasswordChangeSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'app_change_password',
        'app_logout',
        '_wdt',
        '_profiler',
    ];

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof \App\Entity\User) {
            return;
        }

        // Vérifier si l'utilisateur doit changer son mot de passe
        if (!$user->isMustChangePassword()) {
            return;
        }

        // Autoriser certaines routes
        $currentRoute = $event->getRequest()->attributes->get('_route');
        if (in_array($currentRoute, self::ALLOWED_ROUTES, true)) {
            return;
        }

        // Rediriger vers la page de changement de mot de passe
        $response = new RedirectResponse($this->router->generate('app_change_password'));
        $event->setResponse($response);
    }
}
