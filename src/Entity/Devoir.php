<?php

namespace App\Entity;

use App\Repository\DevoirRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Devoir - Évaluation ou travail noté
 * 
 * Représente un devoir, contrôle ou évaluation assigné par un formateur
 * à une session de formation.
 */
#[ORM\Entity(repositoryClass: DevoirRepository::class)]
#[ORM\Table(name: 'devoir')]
#[ORM\Index(columns: ['date_devoir'], name: 'idx_devoir_date')]
#[ORM\Index(columns: ['type'], name: 'idx_devoir_type')]
#[ORM\HasLifecycleCallbacks]
class Devoir
{
    // ========================================
    // CONSTANTES - Types de devoir
    // ========================================
    
    public const TYPE_DEVOIR = 'devoir';
    public const TYPE_CONTROLE = 'controle';
    public const TYPE_EXAMEN = 'examen';
    public const TYPE_TP = 'tp';
    public const TYPE_PROJET = 'projet';
    public const TYPE_ORAL = 'oral';
    public const TYPE_QCM = 'qcm';
    
    public const TYPES = [
        self::TYPE_DEVOIR => 'Devoir maison',
        self::TYPE_CONTROLE => 'Contrôle',
        self::TYPE_EXAMEN => 'Examen',
        self::TYPE_TP => 'TP noté',
        self::TYPE_PROJET => 'Projet',
        self::TYPE_ORAL => 'Oral',
        self::TYPE_QCM => 'QCM',
    ];
    
    // Couleurs pour l'affichage des badges
    public const TYPE_COLORS = [
        self::TYPE_DEVOIR => 'secondary',
        self::TYPE_CONTROLE => 'warning',
        self::TYPE_EXAMEN => 'danger',
        self::TYPE_TP => 'info',
        self::TYPE_PROJET => 'primary',
        self::TYPE_ORAL => 'success',
        self::TYPE_QCM => 'secondary',
    ];

    // ========================================
    // PROPRIÉTÉS
    // ========================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Session de formation concernée
     */
    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La session est obligatoire.')]
    private ?Session $session = null;

    /**
     * Formateur ayant créé le devoir
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le formateur est obligatoire.')]
    private ?User $formateur = null;

    /**
     * Titre du devoir
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne peut dépasser {{ limit }} caractères.')]
    private ?string $titre = null;

    /**
     * Description ou consignes du devoir
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Type de devoir
     */
    #[ORM\Column(length: 20, options: ['default' => self::TYPE_DEVOIR])]
    #[Assert\Choice(
        choices: [
            self::TYPE_DEVOIR,
            self::TYPE_CONTROLE,
            self::TYPE_EXAMEN,
            self::TYPE_TP,
            self::TYPE_PROJET,
            self::TYPE_ORAL,
            self::TYPE_QCM,
        ],
        message: 'Type de devoir invalide.'
    )]
    private string $type = self::TYPE_DEVOIR;

    /**
     * Date du devoir (date de passage ou de rendu)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    private ?\DateTimeInterface $dateDevoir = null;

    /**
     * Date limite de rendu (si applicable)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'dateDevoir',
        message: 'La date limite doit être postérieure ou égale à la date du devoir.'
    )]
    private ?\DateTimeInterface $dateLimite = null;

    /**
     * Coefficient du devoir dans la moyenne
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, options: ['default' => '1.00'])]
    #[Assert\NotNull(message: 'Le coefficient est obligatoire.')]
    #[Assert\Positive(message: 'Le coefficient doit être positif.')]
    #[Assert\LessThanOrEqual(value: 10, message: 'Le coefficient ne peut dépasser 10.')]
    private string $coefficient = '1.00';

    /**
     * Barème (note maximale possible)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '20.00'])]
    #[Assert\NotNull(message: 'Le barème est obligatoire.')]
    #[Assert\Positive(message: 'Le barème doit être positif.')]
    private string $bareme = '20.00';

    /**
     * Devoir visible par les apprenants
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $visible = true;

    /**
     * Notes publiées (visibles par les apprenants)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $notesPubliees = false;

    /**
     * Notes associées à ce devoir
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'devoir', orphanRemoval: true, cascade: ['persist'])]
    private Collection $notes;

    /**
     * Commentaire interne (notes du formateur)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireInterne = null;

    /**
     * Date de création
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
        $this->notes = new ArrayCollection();
        $this->dateDevoir = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    // ========================================
    // GETTERS / SETTERS
    // ========================================

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

    public function getFormateur(): ?User
    {
        return $this->formateur;
    }

    public function setFormateur(?User $formateur): static
    {
        $this->formateur = $formateur;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeLibelle(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColor(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'secondary';
    }

    public function getDateDevoir(): ?\DateTimeInterface
    {
        return $this->dateDevoir;
    }

    public function setDateDevoir(\DateTimeInterface $dateDevoir): static
    {
        $this->dateDevoir = $dateDevoir;
        return $this;
    }

    public function getDateLimite(): ?\DateTimeInterface
    {
        return $this->dateLimite;
    }

    public function setDateLimite(?\DateTimeInterface $dateLimite): static
    {
        $this->dateLimite = $dateLimite;
        return $this;
    }

    public function getCoefficient(): string
    {
        return $this->coefficient;
    }

    public function getCoefficientFloat(): float
    {
        return (float) $this->coefficient;
    }

    public function setCoefficient(string|float $coefficient): static
    {
        $this->coefficient = (string) $coefficient;
        return $this;
    }

    public function getBareme(): string
    {
        return $this->bareme;
    }

    public function getBaremeFloat(): float
    {
        return (float) $this->bareme;
    }

    public function setBareme(string|float $bareme): static
    {
        $this->bareme = (string) $bareme;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function isNotesPubliees(): bool
    {
        return $this->notesPubliees;
    }

    public function setNotesPubliees(bool $notesPubliees): static
    {
        $this->notesPubliees = $notesPubliees;
        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setDevoir($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getDevoir() === $this) {
                $note->setDevoir(null);
            }
        }
        return $this;
    }

    public function getCommentaireInterne(): ?string
    {
        return $this->commentaireInterne;
    }

    public function setCommentaireInterne(?string $commentaireInterne): static
    {
        $this->commentaireInterne = $commentaireInterne;
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
     * Vérifie si le devoir est passé (date dépassée)
     */
    public function isPasse(): bool
    {
        return $this->dateDevoir < new \DateTime('today');
    }

    /**
     * Vérifie si la date limite est dépassée
     */
    public function isDateLimiteDepassee(): bool
    {
        if ($this->dateLimite === null) {
            return false;
        }
        return $this->dateLimite < new \DateTime('today');
    }

    /**
     * Compte le nombre de notes saisies
     */
    public function getNombreNotesSaisies(): int
    {
        return $this->notes->filter(fn(Note $n) => $n->getValeur() !== null)->count();
    }

    /**
     * Compte le nombre d'apprenants inscrits à la session
     */
    public function getNombreApprenants(): int
    {
        return $this->session?->getNombreInscrits() ?? 0;
    }

    /**
     * Vérifie si toutes les notes sont saisies
     */
    public function isComplet(): bool
    {
        return $this->getNombreNotesSaisies() >= $this->getNombreApprenants();
    }

    /**
     * Calcule la moyenne des notes
     */
    public function getMoyenne(): ?float
    {
        $notesAvecValeur = $this->notes->filter(fn(Note $n) => $n->getValeur() !== null);
        
        if ($notesAvecValeur->isEmpty()) {
            return null;
        }
        
        $somme = 0;
        foreach ($notesAvecValeur as $note) {
            $somme += $note->getValeurFloat();
        }
        
        return round($somme / $notesAvecValeur->count(), 2);
    }

    /**
     * Calcule la moyenne ramenée sur 20
     */
    public function getMoyenneSur20(): ?float
    {
        $moyenne = $this->getMoyenne();
        if ($moyenne === null || $this->getBaremeFloat() == 0) {
            return null;
        }
        return round(($moyenne / $this->getBaremeFloat()) * 20, 2);
    }

    /**
     * Récupère la note minimale
     */
    public function getNoteMin(): ?float
    {
        $notesAvecValeur = $this->notes->filter(fn(Note $n) => $n->getValeur() !== null);
        
        if ($notesAvecValeur->isEmpty()) {
            return null;
        }
        
        $min = PHP_FLOAT_MAX;
        foreach ($notesAvecValeur as $note) {
            if ($note->getValeurFloat() < $min) {
                $min = $note->getValeurFloat();
            }
        }
        
        return $min;
    }

    /**
     * Récupère la note maximale
     */
    public function getNoteMax(): ?float
    {
        $notesAvecValeur = $this->notes->filter(fn(Note $n) => $n->getValeur() !== null);
        
        if ($notesAvecValeur->isEmpty()) {
            return null;
        }
        
        $max = PHP_FLOAT_MIN;
        foreach ($notesAvecValeur as $note) {
            if ($note->getValeurFloat() > $max) {
                $max = $note->getValeurFloat();
            }
        }
        
        return $max;
    }

    /**
     * Récupère la note d'un apprenant
     */
    public function getNoteForApprenant(User $apprenant): ?Note
    {
        foreach ($this->notes as $note) {
            if ($note->getApprenant() === $apprenant) {
                return $note;
            }
        }
        return null;
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
        return $this->titre ?? '';
    }
}
