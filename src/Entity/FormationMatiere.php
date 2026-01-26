<?php

namespace App\Entity;

use App\Repository\FormationMatiereRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité FormationMatiere - Liaison Formation ↔ Matière
 * 
 * Cette entité représente l'association entre une formation et une matière,
 * avec les informations spécifiques au référentiel :
 * - Volume horaire prévu
 * - Coefficient (si applicable)
 * - Ordre d'affichage
 */
#[ORM\Entity(repositoryClass: FormationMatiereRepository::class)]
#[ORM\Table(name: 'formation_matiere')]
#[ORM\UniqueConstraint(name: 'unique_formation_matiere', columns: ['formation_id', 'matiere_id'])]
#[ORM\Index(columns: ['ordre'], name: 'idx_formation_matiere_ordre')]
#[UniqueEntity(
    fields: ['formation', 'matiere'],
    message: 'Cette matière est déjà associée à cette formation.',
    errorPath: 'matiere'
)]
class FormationMatiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Formation concernée
     */
    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'formationMatieres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La formation est obligatoire.')]
    private ?Formation $formation = null;

    /**
     * Matière concernée
     */
    #[ORM\ManyToOne(targetEntity: Matiere::class, inversedBy: 'formationMatieres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La matière est obligatoire.')]
    private ?Matiere $matiere = null;

    /**
     * Volume horaire prévu au référentiel (sur la durée totale de la formation)
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'Le volume horaire est obligatoire.')]
    #[Assert\Positive(message: 'Le volume horaire doit être positif.')]
    #[Assert\LessThanOrEqual(value: 9999, message: 'Le volume horaire ne peut pas dépasser {{ compared_value }} heures.')]
    private ?int $volumeHeuresReferentiel = null;

    /**
     * Coefficient de la matière dans la formation (optionnel)
     * Précision: 3 chiffres avant la virgule, 1 après (ex: 2.5, 1.0)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 1, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le coefficient doit être positif ou nul.')]
    #[Assert\LessThanOrEqual(value: 999.9, message: 'Le coefficient ne peut pas dépasser {{ compared_value }}.')]
    private ?string $coefficient = null;

    /**
     * Ordre d'affichage dans la liste des matières de la formation
     */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'L\'ordre doit être positif ou nul.')]
    private int $ordre = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    public function getMatiere(): ?Matiere
    {
        return $this->matiere;
    }

    public function setMatiere(?Matiere $matiere): static
    {
        $this->matiere = $matiere;
        return $this;
    }

    public function getVolumeHeuresReferentiel(): ?int
    {
        return $this->volumeHeuresReferentiel;
    }

    public function setVolumeHeuresReferentiel(int $volumeHeuresReferentiel): static
    {
        $this->volumeHeuresReferentiel = $volumeHeuresReferentiel;
        return $this;
    }

    public function getCoefficient(): ?string
    {
        return $this->coefficient;
    }

    /**
     * Retourne le coefficient sous forme de float
     */
    public function getCoefficientFloat(): ?float
    {
        return $this->coefficient !== null ? (float) $this->coefficient : null;
    }

    public function setCoefficient(?string $coefficient): static
    {
        $this->coefficient = $coefficient;
        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    /**
     * Représentation textuelle
     */
    public function __toString(): string
    {
        if ($this->matiere && $this->formation) {
            return sprintf('%s (%s)', $this->matiere->getCode(), $this->formation->getIntituleCourt());
        }
        return $this->matiere ? (string) $this->matiere : '';
    }
}
