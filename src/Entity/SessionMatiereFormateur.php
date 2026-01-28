<?php

namespace App\Entity;

use App\Repository\SessionMatiereFormateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité SessionMatiereFormateur - Assignation des formateurs aux matières d'une session
 * 
 * Cette entité permet d'assigner un ou plusieurs formateurs à une matière
 * dans le cadre d'une session de formation spécifique.
 * 
 * Règles métier :
 * - Un formateur peut enseigner plusieurs matières dans une session
 * - Une matière peut être enseignée par plusieurs formateurs
 * - Le formateur doit être préalablement assigné à la session
 * - Un formateur peut être désigné "responsable" de la matière
 */
#[ORM\Entity(repositoryClass: SessionMatiereFormateurRepository::class)]
#[ORM\Table(name: 'session_matiere_formateur')]
#[ORM\UniqueConstraint(
    name: 'unique_session_matiere_formateur',
    columns: ['session_matiere_id', 'formateur_id']
)]
#[ORM\Index(columns: ['est_responsable'], name: 'idx_smf_responsable')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['sessionMatiere', 'formateur'],
    message: 'Ce formateur est déjà assigné à cette matière.',
    errorPath: 'formateur'
)]
class SessionMatiereFormateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Matière de session concernée
     */
    #[ORM\ManyToOne(targetEntity: SessionMatiere::class, inversedBy: 'formateurs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La matière de session est obligatoire.')]
    private ?SessionMatiere $sessionMatiere = null;

    /**
     * Formateur assigné
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le formateur est obligatoire.')]
    private ?User $formateur = null;

    /**
     * Nombre d'heures assignées à ce formateur pour cette matière
     * Permet de répartir les heures entre plusieurs formateurs
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Les heures assignées doivent être positives ou nulles.')]
    private ?int $heuresAssignees = null;

    /**
     * Indique si ce formateur est le responsable de la matière
     * (coordinateur pédagogique pour cette matière)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $estResponsable = false;

    /**
     * Commentaire libre (notes sur l'intervention)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * Date de création de l'assignation
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionMatiere(): ?SessionMatiere
    {
        return $this->sessionMatiere;
    }

    public function setSessionMatiere(?SessionMatiere $sessionMatiere): static
    {
        $this->sessionMatiere = $sessionMatiere;
        return $this;
    }

    public function getFormateur(): ?User
    {
        return $this->formateur;
    }

    public function setFormateur(?User $formateur): static
    {
        $this->formateur = $formateur;
        return $this;
    }

    public function getHeuresAssignees(): ?int
    {
        return $this->heuresAssignees;
    }

    public function setHeuresAssignees(?int $heuresAssignees): static
    {
        $this->heuresAssignees = $heuresAssignees;
        return $this;
    }

    public function isEstResponsable(): bool
    {
        return $this->estResponsable;
    }

    public function setEstResponsable(bool $estResponsable): static
    {
        $this->estResponsable = $estResponsable;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
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

    /**
     * Retourne la session parente (raccourci)
     */
    public function getSession(): ?Session
    {
        return $this->sessionMatiere?->getSession();
    }

    /**
     * Retourne la matière (raccourci)
     */
    public function getMatiere(): ?Matiere
    {
        return $this->sessionMatiere?->getMatiere();
    }

    /**
     * Vérifie que le formateur fait bien partie de l'équipe de la session
     */
    public function isFormateurValide(): bool
    {
        if ($this->sessionMatiere === null || $this->formateur === null) {
            return false;
        }

        $session = $this->sessionMatiere->getSession();
        if ($session === null) {
            return false;
        }

        return $session->getFormateurs()->contains($this->formateur);
    }

    public function __toString(): string
    {
        if ($this->formateur && $this->sessionMatiere) {
            return sprintf(
                '%s - %s',
                $this->formateur->getNomComplet(),
                $this->sessionMatiere->getMatiere()?->getCode() ?? ''
            );
        }
        return '';
    }
}
