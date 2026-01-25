<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'role')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'rolesEntities')]
    private Collection $users;

    /**
     * @var Collection<int, Droit>
     */
    #[ORM\ManyToMany(targetEntity: Droit::class, inversedBy: 'roles')]
    #[ORM\JoinTable(name: 'role_droit')]
    private Collection $droits;


    /**
     * Modules accessibles par ce rôle
     */
    #[ORM\ManyToMany(targetEntity: Module::class, mappedBy: 'roles')]
    private Collection $modules;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->droits = new ArrayCollection();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, Droit>
     */
    public function getDroits(): Collection
    {
        return $this->droits;
    }

    public function addDroit(Droit $droit): static
    {
        if (!$this->droits->contains($droit)) {
            $this->droits->add($droit);
        }
        return $this;
    }

    public function removeDroit(Droit $droit): static
    {
        $this->droits->removeElement($droit);
        return $this;
    }

    /**
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->addRole($this);
        }
        return $this;
    }

    public function removeModule(Module $module): static
    {
        if ($this->modules->removeElement($module)) {
            $module->removeRole($this);
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? $this->code ?? '';
    }
}
