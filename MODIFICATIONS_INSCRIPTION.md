# Modifications à apporter aux entités existantes

## 1. Modifier `src/Entity/User.php`

Ajouter la propriété et les méthodes pour la relation inverse avec Inscription :

### Ajouter l'import en haut du fichier :
```php
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
```

### Ajouter la propriété (après `$rolesEntities`) :
```php
/**
 * Inscriptions de l'utilisateur (si apprenti)
 * @var Collection<int, Inscription>
 */
#[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'user', orphanRemoval: true)]
private Collection $inscriptions;
```

### Modifier le constructeur pour initialiser la collection :
```php
public function __construct()
{
    $this->rolesEntities = new ArrayCollection();
    $this->inscriptions = new ArrayCollection();
    $this->dateCreation = new \DateTimeImmutable();
}
```

### Ajouter les méthodes :
```php
/**
 * @return Collection<int, Inscription>
 */
public function getInscriptions(): Collection
{
    return $this->inscriptions;
}

public function addInscription(Inscription $inscription): static
{
    if (!$this->inscriptions->contains($inscription)) {
        $this->inscriptions->add($inscription);
        $inscription->setUser($this);
    }
    return $this;
}

public function removeInscription(Inscription $inscription): static
{
    if ($this->inscriptions->removeElement($inscription)) {
        if ($inscription->getUser() === $this) {
            $inscription->setUser(null);
        }
    }
    return $this;
}

/**
 * Vérifie si l'utilisateur est un apprenti (a le rôle ROLE_APPRENTI)
 */
public function isApprenti(): bool
{
    return in_array('ROLE_APPRENTI', $this->getRoles());
}

/**
 * Retourne les inscriptions actives (validées et en cours)
 */
public function getInscriptionsActives(): Collection
{
    return $this->inscriptions->filter(
        fn(Inscription $i) => $i->isActive()
    );
}
```

---

## 2. Modifier `src/Entity/Session.php`

Ajouter la relation inverse avec Inscription :

### Ajouter la propriété (après `$formateurs`) :
```php
/**
 * Inscriptions à cette session
 * @var Collection<int, Inscription>
 */
#[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'session', orphanRemoval: true)]
#[ORM\OrderBy(['user.nom' => 'ASC'])]
private Collection $inscriptions;
```

### Modifier le constructeur :
```php
public function __construct()
{
    $this->formateurs = new ArrayCollection();
    $this->inscriptions = new ArrayCollection();
    $this->createdAt = new \DateTime();
}
```

### Ajouter les méthodes :
```php
/**
 * @return Collection<int, Inscription>
 */
public function getInscriptions(): Collection
{
    return $this->inscriptions;
}

public function addInscription(Inscription $inscription): static
{
    if (!$this->inscriptions->contains($inscription)) {
        $this->inscriptions->add($inscription);
        $inscription->setSession($this);
    }
    return $this;
}

public function removeInscription(Inscription $inscription): static
{
    if ($this->inscriptions->removeElement($inscription)) {
        if ($inscription->getSession() === $this) {
            $inscription->setSession(null);
        }
    }
    return $this;
}

/**
 * Retourne les inscriptions validées
 */
public function getInscriptionsValidees(): Collection
{
    return $this->inscriptions->filter(
        fn(Inscription $i) => $i->getStatut() === Inscription::STATUT_VALIDEE
    );
}

/**
 * Compte le nombre d'inscrits validés
 */
public function getNombreInscrits(): int
{
    return $this->getInscriptionsValidees()->count();
}

/**
 * Vérifie si la session est complète (effectif max atteint)
 */
public function isComplete(): bool
{
    if ($this->effectifMax === null) {
        return false;
    }
    return $this->getNombreInscrits() >= $this->effectifMax;
}

/**
 * Retourne le nombre de places restantes
 */
public function getPlacesRestantes(): ?int
{
    if ($this->effectifMax === null) {
        return null;
    }
    return max(0, $this->effectifMax - $this->getNombreInscrits());
}
```

---

## 3. Créer la migration

Exécuter ces commandes :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

La migration créera la table `inscription` avec :
- Clé primaire `id`
- Clé étrangère `user_id` vers `user`
- Clé étrangère `session_id` vers `session`
- Contrainte unique sur (`user_id`, `session_id`)
- Index sur `statut` et `date_inscription`

---

## 4. Vérifier le ParamConverter

Le contrôleur utilise un ParamConverter implicite pour `Session`. Symfony 7 le gère automatiquement via l'attribut `#[Route]` avec `{sessionId}`.

Si tu rencontres des erreurs, assure-toi que le ParamConverter est bien configuré. Tu peux ajouter explicitement :

```php
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

// Sur la méthode
#[ParamConverter('session', options: ['id' => 'sessionId'])]
```

Ou utiliser l'injection automatique avec le bon nom de paramètre.

---

## 5. Ajouter le lien dans l'interface Session

Dans ton template `templates/admin/sessions/show.html.twig` (ou équivalent), ajouter un lien vers les inscriptions :

```twig
<a href="{{ path('admin_inscription_index', {sessionId: session.id}) }}" class="btn btn--primary">
    📋 Gérer les inscriptions ({{ session.nombreInscrits }})
</a>
```
