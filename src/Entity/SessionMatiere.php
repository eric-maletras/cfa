<?php

namespace App\Entity;

use App\Repository\SessionMatiereRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité SessionMatiere - Matières d'une session de formation
 * 
 * Cette entité représente la copie des matières du référentiel (FormationMatiere)
 * vers une session spécifique, avec possibilité d'ajuster les volumes horaires.
 * 
 * Workflow :
 * 1. À la création de la session, les FormationMatiere sont copiées en SessionMatiere
 * 2. volumeHeuresReferentiel et coefficient sont copiés depuis FormationMatiere
 * 3. volumeHeuresPlanifie peut être ajusté selon le calendrier de la session
 * 4. volumeHeuresRealise est mis à jour au fil de la formation
 */
#[ORM\Entity(repositoryClass: SessionMatiereRepository::class)]
#[ORM\Table(name: 'session_matiere')]
#[ORM\UniqueConstraint(name: 'unique_session_matiere', columns: ['session_id', 'matiere_id'])]
#[ORM\Index(columns: ['actif'], name: 'idx_session_matiere_actif')]
#[UniqueEntity(
    fields: ['session', 'matiere'],
    message: 'Cette matière est déjà associée à cette session.',
    errorPath: 'matiere'
)]
class SessionMatiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Session concernée
     */
    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'sessionMatieres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La session est obligatoire.')]
    private ?Session $session = null;

    /**
     * Matière concernée
     */
    #[ORM\ManyToOne(targetEntity: Matiere::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La matière est obligatoire.')]
    private ?Matiere $matiere = null;

    /**
     * Volume horaire du référentiel (copié depuis FormationMatiere)
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'Le volume horaire référentiel est obligatoire.')]
    #[Assert\Positive(message: 'Le volume horaire doit être positif.')]
    private ?int $volumeHeuresReferentiel = null;

    /**
     * Volume horaire planifié pour cette session
     * Peut différer du référentiel selon le calendrier
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le volume planifié doit être positif ou nul.')]
    private ?int $volumeHeuresPlanifie = null;

    /**
     * Volume horaire effectivement réalisé
     * Mis à jour au fil de la formation
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le volume réalisé doit être positif ou nul.')]
    private ?int $volumeHeuresRealise = null;

    /**
     * Coefficient de la matière (copié depuis FormationMatiere)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 1, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le coefficient doit être positif ou nul.')]
    private ?string $coefficient = null;

    /**
     * Matière active pour cette session
     * Permet de désactiver une matière sans la supprimer
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Ordre d'affichage (copié depuis FormationMatiere)
     */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $ordre = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;
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

    public function getVolumeHeuresPlanifie(): ?int
    {
        return $this->volumeHeuresPlanifie;
    }

    public function setVolumeHeuresPlanifie(?int $volumeHeuresPlanifie): static
    {
        $this->volumeHeuresPlanifie = $volumeHeuresPlanifie;
        return $this;
    }

    public function getVolumeHeuresRealise(): ?int
    {
        return $this->volumeHeuresRealise;
    }

    public function setVolumeHeuresRealise(?int $volumeHeuresRealise): static
    {
        $this->volumeHeuresRealise = $volumeHeuresRealise;
        return $this;
    }

    public function getCoefficient(): ?string
    {
        return $this->coefficient;
    }

    public function getCoefficientFloat(): ?float
    {
        return $this->coefficient !== null ? (float) $this->coefficient : null;
    }

    public function setCoefficient(?string $coefficient): static
    {
        $this->coefficient = $coefficient;
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
     * Retourne le volume effectif (planifié si défini, sinon référentiel)
     */
    public function getVolumeHeuresEffectif(): int
    {
        return $this->volumeHeuresPlanifie ?? $this->volumeHeuresReferentiel ?? 0;
    }

    /**
     * Calcule le pourcentage de réalisation
     */
    public function getPourcentageRealisation(): ?float
    {
        $effectif = $this->getVolumeHeuresEffectif();
        if ($effectif <= 0 || $this->volumeHeuresRealise === null) {
            return null;
        }
        return round(($this->volumeHeuresRealise / $effectif) * 100, 1);
    }

    /**
     * Calcule l'écart entre planifié et référentiel
     */
    public function getEcartPlanifie(): ?int
    {
        if ($this->volumeHeuresPlanifie === null || $this->volumeHeuresReferentiel === null) {
            return null;
        }
        return $this->volumeHeuresPlanifie - $this->volumeHeuresReferentiel;
    }

    /**
     * Initialise depuis une FormationMatiere
     */
    public function initFromFormationMatiere(FormationMatiere $fm): static
    {
        $this->matiere = $fm->getMatiere();
        $this->volumeHeuresReferentiel = $fm->getVolumeHeuresReferentiel();
        $this->coefficient = $fm->getCoefficient();
        $this->ordre = $fm->getOrdre();
        $this->actif = true;
        
        return $this;
    }

    public function __toString(): string
    {
        if ($this->matiere && $this->session) {
            return sprintf('%s (%s)', $this->matiere->getCode(), $this->session);
        }
        return $this->matiere ? (string) $this->matiere : '';
    }
}
