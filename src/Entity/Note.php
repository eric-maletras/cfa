<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Note - Note attribuée à un apprenant pour un devoir
 * 
 * Représente la note d'un apprenant pour un devoir donné,
 * avec la possibilité d'ajouter un commentaire et de gérer les absences.
 */
#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'note')]
#[ORM\UniqueConstraint(name: 'unique_devoir_apprenant', columns: ['devoir_id', 'apprenant_id'])]
#[ORM\Index(columns: ['valeur'], name: 'idx_note_valeur')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['devoir', 'apprenant'],
    message: 'Une note existe déjà pour cet apprenant sur ce devoir.'
)]
class Note
{
    // ========================================
    // CONSTANTES - Statuts spéciaux
    // ========================================
    
    public const STATUT_NORMAL = 'normal';
    public const STATUT_ABSENT = 'absent';
    public const STATUT_DISPENSE = 'dispense';
    public const STATUT_RATTRAPAGE = 'rattrapage';
    
    public const STATUTS = [
        self::STATUT_NORMAL => 'Normal',
        self::STATUT_ABSENT => 'Absent',
        self::STATUT_DISPENSE => 'Dispensé',
        self::STATUT_RATTRAPAGE => 'Rattrapage',
    ];

    // ========================================
    // PROPRIÉTÉS
    // ========================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Devoir concerné
     */
    #[ORM\ManyToOne(targetEntity: Devoir::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le devoir est obligatoire.')]
    private ?Devoir $devoir = null;

    /**
     * Apprenant noté (User avec ROLE_APPRENANT)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'apprenant est obligatoire.')]
    private ?User $apprenant = null;

    /**
     * Valeur de la note (null si non saisie ou absent)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'La note ne peut être négative.')]
    private ?string $valeur = null;

    /**
     * Statut de la note (normal, absent, dispensé, rattrapage)
     */
    #[ORM\Column(length: 20, options: ['default' => self::STATUT_NORMAL])]
    #[Assert\Choice(
        choices: [
            self::STATUT_NORMAL,
            self::STATUT_ABSENT,
            self::STATUT_DISPENSE,
            self::STATUT_RATTRAPAGE,
        ],
        message: 'Statut invalide.'
    )]
    private string $statut = self::STATUT_NORMAL;

    /**
     * Commentaire du formateur sur la note
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * Date de saisie de la note
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSaisie = null;

    /**
     * Formateur ayant saisi la note
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $saisiePar = null;

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

    // ========================================
    // CONSTRUCTEUR
    // ========================================

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ========================================
    // GETTERS / SETTERS
    // ========================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDevoir(): ?Devoir
    {
        return $this->devoir;
    }

    public function setDevoir(?Devoir $devoir): static
    {
        $this->devoir = $devoir;
        return $this;
    }

    public function getApprenant(): ?User
    {
        return $this->apprenant;
    }

    public function setApprenant(?User $apprenant): static
    {
        $this->apprenant = $apprenant;
        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function getValeurFloat(): ?float
    {
        return $this->valeur !== null ? (float) $this->valeur : null;
    }

    public function setValeur(string|float|null $valeur): static
    {
        $this->valeur = $valeur !== null ? (string) $valeur : null;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(?\DateTimeInterface $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;
        return $this;
    }

    public function getSaisiePar(): ?User
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?User $saisiePar): static
    {
        $this->saisiePar = $saisiePar;
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

    // ========================================
    // MÉTHODES MÉTIER
    // ========================================

    /**
     * Vérifie si la note a été saisie
     */
    public function isSaisie(): bool
    {
        return $this->valeur !== null || $this->statut !== self::STATUT_NORMAL;
    }

    /**
     * Vérifie si l'apprenant était absent
     */
    public function isAbsent(): bool
    {
        return $this->statut === self::STATUT_ABSENT;
    }

    /**
     * Vérifie si l'apprenant est dispensé
     */
    public function isDispense(): bool
    {
        return $this->statut === self::STATUT_DISPENSE;
    }

    /**
     * Vérifie si c'est un rattrapage
     */
    public function isRattrapage(): bool
    {
        return $this->statut === self::STATUT_RATTRAPAGE;
    }

    /**
     * Vérifie si la note compte dans la moyenne
     */
    public function comptesDansMoyenne(): bool
    {
        // Ne compte pas si absent ou dispensé
        return !in_array($this->statut, [self::STATUT_ABSENT, self::STATUT_DISPENSE]);
    }

    /**
     * Calcule la note sur 20 (si barème différent)
     */
    public function getValeurSur20(): ?float
    {
        if ($this->valeur === null || $this->devoir === null) {
            return null;
        }
        
        $bareme = $this->devoir->getBaremeFloat();
        if ($bareme == 0) {
            return null;
        }
        
        return round(($this->getValeurFloat() / $bareme) * 20, 2);
    }

    /**
     * Formate la note pour affichage
     */
    public function getValeurFormatee(): string
    {
        if ($this->statut === self::STATUT_ABSENT) {
            return 'ABS';
        }
        
        if ($this->statut === self::STATUT_DISPENSE) {
            return 'DISP';
        }
        
        if ($this->valeur === null) {
            return '-';
        }
        
        $bareme = $this->devoir?->getBareme() ?? '20';
        return number_format($this->getValeurFloat(), 2, ',', '') . '/' . $bareme;
    }

    /**
     * Détermine la classe CSS en fonction de la note
     */
    public function getNoteClass(): string
    {
        if ($this->statut === self::STATUT_ABSENT) {
            return 'note--absent';
        }
        
        if ($this->statut === self::STATUT_DISPENSE) {
            return 'note--dispense';
        }
        
        if ($this->valeur === null) {
            return 'note--vide';
        }
        
        $sur20 = $this->getValeurSur20() ?? 0;
        
        if ($sur20 >= 16) {
            return 'note--excellent';
        } elseif ($sur20 >= 14) {
            return 'note--bien';
        } elseif ($sur20 >= 12) {
            return 'note--assez-bien';
        } elseif ($sur20 >= 10) {
            return 'note--passable';
        } elseif ($sur20 >= 8) {
            return 'note--insuffisant';
        } else {
            return 'note--faible';
        }
    }

    // ========================================
    // CALLBACKS DOCTRINE
    // ========================================

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->valeur !== null && $this->dateSaisie === null) {
            $this->dateSaisie = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
        
        // Mise à jour de la date de saisie si la valeur change
        if ($this->valeur !== null) {
            $this->dateSaisie = new \DateTime();
        }
    }

    // ========================================
    // REPRÉSENTATION
    // ========================================

    public function __toString(): string
    {
        return $this->getValeurFormatee();
    }
}
