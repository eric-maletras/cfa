# Modifications à apporter à l'entité User

## 1. Ajouter le champ `mustChangePassword` dans `src/Entity/User.php`

Ajouter cette propriété après `derniereConnexion` :

```php
#[ORM\Column(name: 'must_change_password', options: ['default' => false])]
private bool $mustChangePassword = false;
```

Ajouter les getter/setter :

```php
public function isMustChangePassword(): bool
{
    return $this->mustChangePassword;
}

public function setMustChangePassword(bool $mustChangePassword): static
{
    $this->mustChangePassword = $mustChangePassword;
    return $this;
}
```

## 2. Créer la migration

Exécuter ces commandes :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Ou exécuter directement le SQL :

```sql
ALTER TABLE `user` ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0;
```

## 3. (Optionnel) Forcer le changement de mot de passe à la connexion

Créer un EventSubscriber pour rediriger vers une page de changement de mot de passe
si `mustChangePassword` est true après la connexion.

Exemple de subscriber `src/EventSubscriber/PasswordChangeSubscriber.php` :

```php
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
```

Ce subscriber :
- Intercepte chaque requête
- Vérifie si l'utilisateur connecté doit changer son mot de passe
- Le redirige vers une page dédiée si c'est le cas
- Autorise seulement certaines routes (changement de mot de passe, déconnexion, debug)
