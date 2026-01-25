<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Inscription - Association entre un apprenti et une session de formation
 * 
 * Représente l'inscription d'un apprenti à une session donnée,
 * avec son statut, son option choisie, et les métadonnées associées.
 */
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ORM\Table(name: 'inscription')]
#[ORM\Index(columns: ['statut'], name: 'idx_inscription_statut')]
#[ORM\Index(columns: ['date_inscription'], name: 'idx_inscription_date')]
#[ORM\UniqueConstraint(name: 'unique_user_session', columns: ['user_id', 'session_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['user', 'session'],
    message: 'Cet apprenti est déjà inscrit à cette session.'
)]
class Inscription
{
    // ========================================
    // CONSTANTES - Statuts d'inscription
    // ========================================
    
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE = 'validee';
    public const STATUT_REFUSEE = 'refusee';
    public const STATUT_ANNULEE = 'annulee';
    public const STATUT_ABANDONNEE = 'abandonnee';
    public const STATUT_TERMINEE = 'terminee';
    
    public const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente de validation',
        self::STATUT_VALIDEE => 'Validée',
        self::STATUT_REFUSEE => 'Refusée',
        self::STATUT_ANNULEE => 'Annulée',
        self::STATUT_ABANDONNEE => 'Abandon',
        self::STATUT_TERMINEE => 'Formation terminée',
    ];
    
    // Couleurs pour l'affichage des badges
    public const STATUT_COLORS = [
        self::STATUT_EN_ATTENTE => 'warning',
        self::STATUT_VALIDEE => 'success',
        self::STATUT_REFUSEE => 'danger',
        self::STATUT_ANNULEE => 'secondary',
        self::STATUT_ABANDONNEE => 'danger',
        self::STATUT_TERMINEE => 'info',
    ];

    // ========================================
    // PROPRIÉTÉS
    // ========================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Apprenti inscrit (User avec ROLE_APPRENTI)
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'apprenti est obligatoire.')]
    private ?User $user = null;

    /**
     * Session de formation
     */
    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La session est obligatoire.')]
    private ?Session $session = null;

    /**
     * Date d'inscription
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date d\'inscription est obligatoire.')]
    private ?\DateTimeInterface $dateInscription = null;

    /**
     * Statut de l'inscription
     */
    #[ORM\Column(length: 20, options: ['default' => self::STATUT_EN_ATTENTE])]
    #[Assert\Choice(
        choices: [
            self::STATUT_EN_ATTENTE,
            self::STATUT_VALIDEE,
            self::STATUT_REFUSEE,
            self::STATUT_ANNULEE,
            self::STATUT_ABANDONNEE,
            self::STATUT_TERMINEE,
        ],
        message: 'Statut invalide.'
    )]
    private string $statut = self::STATUT_EN_ATTENTE;

    /**
     * Option choisie (si la formation propose des options)
     * Ex: "SISR", "SLAM" pour un BTS SIO
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $option = null;

    /**
     * Numéro de contrat d'apprentissage (si connu)
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numeroContrat = null;

    /**
     * Date de début effective (peut différer de la date de début de session)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutEffective = null;

    /**
     * Date de fin effective (en cas d'abandon ou fin anticipée)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinEffective = null;

    /**
     * Motif en cas de refus, annulation ou abandon
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    /**
     * Commentaire interne (notes administratives)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

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
     * Utilisateur ayant créé l'inscription
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    // ========================================
    // CONSTRUCTEUR
    // ========================================

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    // ========================================
    // GETTERS / SETTERS
    // ========================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
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

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getStatutLibelle(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColor(): string
    {
        return self::STATUT_COLORS[$this->statut] ?? 'secondary';
    }

    public function getOption(): ?string
    {
        return $this->option;
    }

    public function setOption(?string $option): static
    {
        $this->option = $option;
        return $this;
    }

    public function getNumeroContrat(): ?string
    {
        return $this->numeroContrat;
    }

    public function setNumeroContrat(?string $numeroContrat): static
    {
        $this->numeroContrat = $numeroContrat;
        return $this;
    }

    public function getDateDebutEffective(): ?\DateTimeInterface
    {
        return $this->dateDebutEffective;
    }

    public function setDateDebutEffective(?\DateTimeInterface $dateDebutEffective): static
    {
        $this->dateDebutEffective = $dateDebutEffective;
        return $this;
    }

    public function getDateFinEffective(): ?\DateTimeInterface
    {
        return $this->dateFinEffective;
    }

    public function setDateFinEffective(?\DateTimeInterface $dateFinEffective): static
    {
        $this->dateFinEffective = $dateFinEffective;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    // ========================================
    // MÉTHODES MÉTIER
    // ========================================

    /**
     * Vérifie si l'inscription est active (validée et en cours)
     */
    public function isActive(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    /**
     * Vérifie si l'inscription peut être modifiée
     */
    public function isModifiable(): bool
    {
        return in_array($this->statut, [
            self::STATUT_EN_ATTENTE,
            self::STATUT_VALIDEE,
        ]);
    }

    /**
     * Vérifie si l'inscription peut être annulée
     */
    public function isAnnulable(): bool
    {
        return in_array($this->statut, [
            self::STATUT_EN_ATTENTE,
            self::STATUT_VALIDEE,
        ]);
    }

    /**
     * Valide l'inscription
     */
    public function valider(): static
    {
        $this->statut = self::STATUT_VALIDEE;
        $this->motif = null;
        return $this;
    }

    /**
     * Refuse l'inscription
     */
    public function refuser(string $motif): static
    {
        $this->statut = self::STATUT_REFUSEE;
        $this->motif = $motif;
        return $this;
    }

    /**
     * Annule l'inscription
     */
    public function annuler(string $motif): static
    {
        $this->statut = self::STATUT_ANNULEE;
        $this->motif = $motif;
        $this->dateFinEffective = new \DateTime();
        return $this;
    }

    /**
     * Marque l'inscription comme abandon
     */
    public function abandonner(string $motif, ?\DateTimeInterface $dateFin = null): static
    {
        $this->statut = self::STATUT_ABANDONNEE;
        $this->motif = $motif;
        $this->dateFinEffective = $dateFin ?? new \DateTime();
        return $this;
    }

    /**
     * Marque la formation comme terminée
     */
    public function terminer(): static
    {
        $this->statut = self::STATUT_TERMINEE;
        $this->dateFinEffective = $this->session?->getDateFin() ?? new \DateTime();
        return $this;
    }

    // ========================================
    // CALLBACKS DOCTRINE
    // ========================================

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ========================================
    // REPRÉSENTATION
    // ========================================

    public function __toString(): string
    {
        $userStr = $this->user?->getNomComplet() ?? 'Inconnu';
        $sessionStr = $this->session?->getCode() ?? 'Inconnue';
        return sprintf('%s - %s', $userStr, $sessionStr);
    }
}
