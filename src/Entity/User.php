<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(name: 'date_creation')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(name: 'derniere_connexion', nullable: true)]
    private ?\DateTimeImmutable $derniereConnexion = null;

    #[ORM\Column(name: 'must_change_password', options: ['default' => false])]
    private bool $mustChangePassword = false;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_role')]
    private Collection $rolesEntities;

    /**
     * Inscriptions de l'utilisateur (si apprenti)
     * @var Collection<int, Inscription>
    */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $inscriptions;

    public function __construct()
    {
        $this->rolesEntities = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDerniereConnexion(): ?\DateTimeImmutable
    {
        return $this->derniereConnexion;
    }

    public function setDerniereConnexion(?\DateTimeImmutable $derniereConnexion): static
    {
        $this->derniereConnexion = $derniereConnexion;
        return $this;
    }
    
    public function isMustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function setMustChangePassword(bool $mustChangePassword): static
    {
        $this->mustChangePassword = $mustChangePassword;
        return $this;
    }

    

    /**
     * @return Collection<int, Role>
     */
    public function getRolesEntities(): Collection
    {
        return $this->rolesEntities;
    }

    public function addRolesEntity(Role $role): static
    {
        if (!$this->rolesEntities->contains($role)) {
            $this->rolesEntities->add($role);
        }
        return $this;
    }

    public function removeRolesEntity(Role $role): static
    {
        $this->rolesEntities->removeElement($role);
        return $this;
    }

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

    /**
     * Requis par UserInterface - retourne les codes des rôles
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->rolesEntities as $role) {
            $roles[] = $role->getCode();
        }
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function __toString(): string
    {
        return $this->getNomComplet();
    }
}
