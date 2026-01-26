<?php

namespace App\Entity;

use App\Repository\MatiereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Matiere - Référentiel des matières enseignées
 * 
 * Cette entité représente le catalogue des matières disponibles
 * (ex: SLAM, SISR, MATH, ANGL, etc.)
 * Les matières sont liées aux formations via FormationMatiere
 */
#[ORM\Entity(repositoryClass: MatiereRepository::class)]
#[ORM\Table(name: 'matiere')]
#[ORM\Index(columns: ['actif'], name: 'idx_matiere_actif')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['code'], message: 'Ce code de matière existe déjà.')]
class Matiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code unique de la matière (ex: "SLAM", "SISR", "MATH")
     */
    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Le code est obligatoire.')]
    #[Assert\Length(max: 20, maxMessage: 'Le code ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9\-]+$/',
        message: 'Le code ne peut contenir que des lettres majuscules, chiffres et tirets.'
    )]
    private ?string $code = null;

    /**
     * Libellé complet de la matière
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $libelle = null;

    /**
     * Description détaillée de la matière (optionnel)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Matière active (utilisable dans les formations)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Date de création de l'enregistrement
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Date de dernière modification
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Liaisons avec les formations (via FormationMatiere)
     * @var Collection<int, FormationMatiere>
     */
    #[ORM\OneToMany(targetEntity: FormationMatiere::class, mappedBy: 'matiere', orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $formationMatieres;

    public function __construct()
    {
        $this->formationMatieres = new ArrayCollection();
        $this->createdAt = new \DateTime();
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
        $this->code = strtoupper($code);
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

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, FormationMatiere>
     */
    public function getFormationMatieres(): Collection
    {
        return $this->formationMatieres;
    }

    public function addFormationMatiere(FormationMatiere $formationMatiere): static
    {
        if (!$this->formationMatieres->contains($formationMatiere)) {
            $this->formationMatieres->add($formationMatiere);
            $formationMatiere->setMatiere($this);
        }
        return $this;
    }

    public function removeFormationMatiere(FormationMatiere $formationMatiere): static
    {
        if ($this->formationMatieres->removeElement($formationMatiere)) {
            if ($formationMatiere->getMatiere() === $this) {
                $formationMatiere->setMatiere(null);
            }
        }
        return $this;
    }

    /**
     * Retourne le nombre de formations utilisant cette matière
     */
    public function getNombreFormations(): int
    {
        return $this->formationMatieres->count();
    }

    /**
     * Retourne les formations utilisant cette matière
     * 
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formationMatieres->map(
            fn(FormationMatiere $fm) => $fm->getFormation()
        );
    }

    /**
     * Met à jour la date de modification avant persistence
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Représentation textuelle
     */
    public function __toString(): string
    {
        return $this->code ? sprintf('%s - %s', $this->code, $this->libelle) : '';
    }
}
