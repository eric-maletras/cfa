<?php

namespace App\Entity;

use App\Enum\StatutSeance;
use App\Repository\SeancePlanifieeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Entité SeancePlanifiee - Occurrence concrète d'une séance de cours
 * 
 * Une séance planifiée est générée depuis un CreneauRecurrent (automatiquement)
 * ou créée manuellement (creneauRecurrent = null).
 * 
 * Les données sont dénormalisées depuis le créneau pour permettre des
 * modifications unitaires (changement de salle, formateur, horaire).
 * 
 * Le flag modifieeDepuisCreneau protège les séances modifiées manuellement
 * lors de la régénération du créneau.
 */
#[ORM\Entity(repositoryClass: SeancePlanifieeRepository::class)]
#[ORM\Table(name: 'seance_planifiee')]
#[ORM\Index(columns: ['date'], name: 'idx_seance_date')]
#[ORM\Index(columns: ['statut'], name: 'idx_seance_statut')]
#[ORM\Index(columns: ['modifiee_depuis_creneau'], name: 'idx_seance_modifiee')]
#[ORM\HasLifecycleCallbacks]
class SeancePlanifiee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Créneau récurrent source (null si séance manuelle)
     */
    #[ORM\ManyToOne(targetEntity: CreneauRecurrent::class, inversedBy: 'seances')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CreneauRecurrent $creneauRecurrent = null;

    /**
     * Session concernée (dénormalisé)
     */
    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La session est obligatoire.')]
    private ?Session $session = null;

    /**
     * Matière de la session (dénormalisé)
     */
    #[ORM\ManyToOne(targetEntity: SessionMatiere::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La matière est obligatoire.')]
    private ?SessionMatiere $sessionMatiere = null;

    /**
     * Salle de cours (dénormalisé)
     */
    #[ORM\ManyToOne(targetEntity: Salle::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La salle est obligatoire.')]
    private ?Salle $salle = null;

    /**
     * Formateurs intervenant sur cette séance (dénormalisé)
     * 
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'seance_planifiee_formateur')]
    #[Assert\Count(min: 1, minMessage: 'Au moins un formateur doit être assigné.')]
    private Collection $formateurs;

    /**
     * Date de la séance
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    private ?\DateTimeInterface $date = null;

    /**
     * Heure de début
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire.")]
    private ?\DateTimeInterface $heureDebut = null;

    /**
     * Heure de fin
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire.")]
    private ?\DateTimeInterface $heureFin = null;

    /**
     * Statut de la séance
     */
    #[ORM\Column(type: 'string', length: 20, enumType: StatutSeance::class)]
    #[Assert\NotNull(message: 'Le statut est obligatoire.')]
    private StatutSeance $statut = StatutSeance::PLANIFIEE;

    /**
     * Commentaire libre (notes, raison d'annulation, etc.)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * Indique si la séance a été modifiée manuellement depuis le créneau
     * Protège la séance lors de la régénération
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $modifieeDepuisCreneau = false;

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

    public function __construct()
    {
        $this->formateurs = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->statut = StatutSeance::PLANIFIEE;
    }

    // ========================================
    // GETTERS / SETTERS
    // ========================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreneauRecurrent(): ?CreneauRecurrent
    {
        return $this->creneauRecurrent;
    }

    public function setCreneauRecurrent(?CreneauRecurrent $creneauRecurrent): static
    {
        $this->creneauRecurrent = $creneauRecurrent;
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

    public function getSessionMatiere(): ?SessionMatiere
    {
        return $this->sessionMatiere;
    }

    public function setSessionMatiere(?SessionMatiere $sessionMatiere): static
    {
        $this->sessionMatiere = $sessionMatiere;
        return $this;
    }

    public function getSalle(): ?Salle
    {
        return $this->salle;
    }

    public function setSalle(?Salle $salle): static
    {
        $this->salle = $salle;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFormateurs(): Collection
    {
        return $this->formateurs;
    }

    public function addFormateur(User $formateur): static
    {
        if (!$this->formateurs->contains($formateur)) {
            $this->formateurs->add($formateur);
        }
        return $this;
    }

    public function removeFormateur(User $formateur): static
    {
        $this->formateurs->removeElement($formateur);
        return $this;
    }

    /**
     * Remplace tous les formateurs
     * 
     * @param iterable<User> $formateurs
     */
    public function setFormateurs(iterable $formateurs): static
    {
        $this->formateurs->clear();
        foreach ($formateurs as $formateur) {
            $this->addFormateur($formateur);
        }
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getStatut(): StatutSeance
    {
        return $this->statut;
    }

    public function setStatut(StatutSeance $statut): static
    {
        $this->statut = $statut;
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

    public function isModifieeDepuisCreneau(): bool
    {
        return $this->modifieeDepuisCreneau;
    }

    public function setModifieeDepuisCreneau(bool $modifieeDepuisCreneau): static
    {
        $this->modifieeDepuisCreneau = $modifieeDepuisCreneau;
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
     * Vérifie si c'est une séance manuelle (sans créneau récurrent)
     */
    public function isManuelle(): bool
    {
        return $this->creneauRecurrent === null;
    }

    /**
     * Calcule la durée en minutes
     */
    public function getDureeMinutes(): int
    {
        if (!$this->heureDebut || !$this->heureFin) {
            return 0;
        }
        
        $debut = (int) $this->heureDebut->format('H') * 60 + (int) $this->heureDebut->format('i');
        $fin = (int) $this->heureFin->format('H') * 60 + (int) $this->heureFin->format('i');
        
        return max(0, $fin - $debut);
    }

    /**
     * Calcule la durée en heures (décimales)
     */
    public function getDureeHeures(): float
    {
        return round($this->getDureeMinutes() / 60, 2);
    }

    /**
     * Retourne la plage horaire formatée
     */
    public function getPlageHoraire(): string
    {
        if (!$this->heureDebut || !$this->heureFin) {
            return '';
        }
        return sprintf(
            '%s - %s',
            $this->heureDebut->format('H:i'),
            $this->heureFin->format('H:i')
        );
    }

    /**
     * Retourne la date formatée
     */
    public function getDateFormatee(): string
    {
        if (!$this->date) {
            return '';
        }
        
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE
        );
        
        return ucfirst($formatter->format($this->date));
    }

    /**
     * Retourne le jour de la semaine en français
     */
    public function getJourSemaine(): string
    {
        if (!$this->date) {
            return '';
        }
        
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];
        
        return $jours[(int) $this->date->format('N')] ?? '';
    }

    /**
     * Vérifie si la séance est dans le passé
     */
    public function isPassee(): bool
    {
        if (!$this->date) {
            return false;
        }
        return $this->date < (new \DateTime())->setTime(0, 0, 0);
    }

    /**
     * Vérifie si la séance est aujourd'hui
     */
    public function isAujourdHui(): bool
    {
        if (!$this->date) {
            return false;
        }
        return $this->date->format('Y-m-d') === (new \DateTime())->format('Y-m-d');
    }

    /**
     * Vérifie si la séance est dans le futur
     */
    public function isFuture(): bool
    {
        if (!$this->date) {
            return false;
        }
        return $this->date > (new \DateTime())->setTime(23, 59, 59);
    }

    /**
     * Vérifie si la séance est active (non annulée)
     */
    public function isActive(): bool
    {
        return $this->statut->isActive();
    }

    /**
     * Vérifie si la séance peut être modifiée
     */
    public function isModifiable(): bool
    {
        return $this->statut->isModifiable();
    }

    /**
     * Retourne les noms des formateurs
     */
    public function getFormateursNoms(): string
    {
        $noms = $this->formateurs->map(
            fn(User $u) => $u->getNomComplet()
        )->toArray();
        
        return implode(', ', $noms);
    }

    /**
     * Retourne la matière (raccourci)
     */
    public function getMatiere(): ?Matiere
    {
        return $this->sessionMatiere?->getMatiere();
    }

    /**
     * Initialise depuis un créneau récurrent
     */
    public function initFromCreneau(CreneauRecurrent $creneau, \DateTimeInterface $date): static
    {
        $this->creneauRecurrent = $creneau;
        $this->session = $creneau->getSession();
        $this->sessionMatiere = $creneau->getSessionMatiere();
        $this->salle = $creneau->getSalle();
        $this->date = $date instanceof \DateTime ? clone $date : \DateTime::createFromInterface($date);
        $this->heureDebut = clone $creneau->getHeureDebut();
        $this->heureFin = clone $creneau->getHeureFin();
        $this->statut = StatutSeance::PLANIFIEE;
        $this->modifieeDepuisCreneau = false;
        
        // Copier les formateurs
        foreach ($creneau->getFormateurs() as $formateur) {
            $this->addFormateur($formateur);
        }
        
        return $this;
    }

    /**
     * Marque la séance comme modifiée manuellement
     */
    public function marquerModifiee(): static
    {
        $this->modifieeDepuisCreneau = true;
        return $this;
    }

    /**
     * Retourne le type de semaine (A ou B)
     */
    public function getSemaineType(): ?string
    {
        if (!$this->date) {
            return null;
        }
        $semaineIso = (int) $this->date->format('W');
        return ($semaineIso % 2 === 1) ? 'A' : 'B';
    }

    // ========================================
    // VALIDATIONS
    // ========================================

    #[Assert\Callback]
    public function validateHeures(ExecutionContextInterface $context): void
    {
        if ($this->heureDebut && $this->heureFin) {
            if ($this->heureDebut >= $this->heureFin) {
                $context->buildViolation("L'heure de fin doit être postérieure à l'heure de début.")
                    ->atPath('heureFin')
                    ->addViolation();
            }
        }
    }

    /**
     * Validation : heures par tranches de 15 minutes
     */
    #[Assert\Callback]
    public function validateTranchesHoraires(ExecutionContextInterface $context): void
    {
        foreach (['heureDebut' => $this->heureDebut, 'heureFin' => $this->heureFin] as $field => $heure) {
            if ($heure) {
                $minutes = (int) $heure->format('i');
                if ($minutes % 15 !== 0) {
                    $context->buildViolation('Les heures doivent être par tranches de 15 minutes (00, 15, 30, 45).')
                        ->atPath($field)
                        ->addViolation();
                }
            }
        }
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
        $matiere = $this->sessionMatiere?->getMatiere()?->getCode() ?? '?';
        $date = $this->date?->format('d/m/Y') ?? '?';
        $horaire = $this->getPlageHoraire() ?: '?';
        
        return sprintf('%s - %s %s', $matiere, $date, $horaire);
    }
}
