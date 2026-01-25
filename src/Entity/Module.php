<?php
// src/Entity/Module.php

namespace App\Entity;

use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Module - Représente une fonctionnalité/module accessible depuis le dashboard
 * 
 * Les modules sont affichés dynamiquement selon les rôles de l'utilisateur connecté.
 * Un module s'affiche si l'utilisateur possède au moins un des rôles associés.
 */
#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'module')]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du module affiché sur le dashboard
     */
    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    /**
     * Description courte du module
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Nom de l'icône (ex: 'users', 'calendar', 'file-text')
     * Utilise les noms d'icônes Lucide/Feather
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icone = null;

    /**
     * Nom de la route Symfony (ex: 'app_admin_users')
     */
    #[ORM\Column(length: 100)]
    private ?string $route = null;

    /**
     * Couleur du module : 'primary', 'secondary', 'tertiary'
     */
    #[ORM\Column(length: 20, options: ['default' => 'primary'])]
    private ?string $couleur = 'primary';

    /**
     * Ordre d'affichage (plus petit = affiché en premier)
     */
    #[ORM\Column(options: ['default' => 0])]
    private ?int $ordre = 0;

    /**
     * Module actif ou non
     */
    #[ORM\Column(options: ['default' => true])]
    private ?bool $actif = true;

    /**
     * Module parent (pour créer des sous-menus, nullable)
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'sousModules')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /**
     * Sous-modules
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $sousModules;

    /**
     * Rôles ayant accès à ce module
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'modules')]
    #[ORM\JoinTable(name: 'module_role')]
    private Collection $roles;

    public function __construct()
    {
        $this->sousModules = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSousModules(): Collection
    {
        return $this->sousModules;
    }

    public function addSousModule(self $sousModule): static
    {
        if (!$this->sousModules->contains($sousModule)) {
            $this->sousModules->add($sousModule);
            $sousModule->setParent($this);
        }
        return $this;
    }

    public function removeSousModule(self $sousModule): static
    {
        if ($this->sousModules->removeElement($sousModule)) {
            if ($sousModule->getParent() === $this) {
                $sousModule->setParent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);
        return $this;
    }

    /**
     * Vérifie si un utilisateur a accès à ce module
     * L'utilisateur doit avoir au moins un des rôles associés au module
     */
    public function isAccessibleByUser(User $user): bool
    {
        if (!$this->actif) {
            return false;
        }

        foreach ($user->getUserRoles() as $userRole) {
            if ($this->roles->contains($userRole->getRole())) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
