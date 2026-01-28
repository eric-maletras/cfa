<?php

namespace App\Entity;

use App\Repository\SalleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Entité Salle - Référentiel des salles de formation
 * 
 * Cette entité représente les salles disponibles pour les cours :
 * salles de cours, laboratoires informatiques, laboratoires optiques,
 * amphithéâtres et salles virtuelles (distanciel).
 * 
 * La salle virtuelle (code VIRTUEL) a une capacité illimitée (null).
 */
#[ORM\Entity(repositoryClass: SalleRepository::class)]
#[ORM\Table(name: 'salle')]
#[ORM\Index(columns: ['actif'], name: 'idx_salle_actif')]
#[ORM\Index(columns: ['type'], name: 'idx_salle_type')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['code'], message: 'Ce code de salle existe déjà.')]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code unique de la salle (ex: "A101", "LABO-IT-1", "VIRTUEL")
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
     * Libellé complet de la salle
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $libelle = null;

    /**
     * Capacité maximale de la salle (null = illimitée pour virtuel)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'La capacité doit être un nombre positif.')]
    #[Assert\LessThanOrEqual(value: 500, message: 'La capacité ne peut pas dépasser {{ compared_value }} places.')]
    private ?int $capacite = null;

    /**
     * Type de la salle
     */
    #[ORM\Column(length: 20, enumType: TypeSalle::class)]
    #[Assert\NotNull(message: 'Le type de salle est obligatoire.')]
    private ?TypeSalle $type = null;

    /**
     * Description détaillée de la salle (optionnel)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Salle active (utilisable dans les plannings)
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

    public function __construct()
    {
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

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getType(): ?TypeSalle
    {
        return $this->type;
    }

    public function setType(TypeSalle $type): static
    {
        $this->type = $type;
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
     * Indique si la salle est virtuelle (distanciel)
     */
    public function isVirtuel(): bool
    {
        return $this->type === TypeSalle::VIRTUEL;
    }

    /**
     * Retourne la capacité formatée pour l'affichage
     */
    public function getCapaciteFormatee(): string
    {
        if ($this->capacite === null) {
            return $this->isVirtuel() ? 'Illimitée' : 'Non définie';
        }
        return (string) $this->capacite . ' places';
    }

    /**
     * Validation callback : capacité obligatoire sauf pour les salles virtuelles
     */
    #[Assert\Callback]
    public function validateCapacite(ExecutionContextInterface $context): void
    {
        // Si le type nécessite une capacité et qu'elle n'est pas définie
        if ($this->type !== null && $this->type->requiresCapacite() && $this->capacite === null) {
            $context->buildViolation('La capacité est obligatoire pour ce type de salle.')
                ->atPath('capacite')
                ->addViolation();
        }

        // Si le type est virtuel, la capacité doit être null
        if ($this->type === TypeSalle::VIRTUEL && $this->capacite !== null) {
            $context->buildViolation('Une salle virtuelle ne peut pas avoir de capacité définie.')
                ->atPath('capacite')
                ->addViolation();
        }
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
