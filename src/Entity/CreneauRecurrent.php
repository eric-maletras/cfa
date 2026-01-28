<?php

namespace App\Entity;

use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use App\Repository\CreneauRecurrentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Entité CreneauRecurrent - Template de répétition pour le planning
 * 
 * Un créneau récurrent définit un cours qui se répète chaque semaine
 * (ou en alternance A/B) sur une période donnée.
 * 
 * Les séances planifiées (SeancePlanifiee) sont générées automatiquement
 * à partir de ce créneau par le service GenerateurSeancesService.
 * 
 * Exemple : "SI7 - Lundi 8h-10h - Salle LABO-IT-1 - Toutes les semaines du 02/09 au 30/06"
 */
#[ORM\Entity(repositoryClass: CreneauRecurrentRepository::class)]
#[ORM\Table(name: 'creneau_recurrent')]
#[ORM\Index(columns: ['actif'], name: 'idx_creneau_actif')]
#[ORM\Index(columns: ['jour_semaine'], name: 'idx_creneau_jour')]
#[ORM\HasLifecycleCallbacks]
class CreneauRecurrent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Session concernée
     */
    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La session est obligatoire.')]
    private ?Session $session = null;

    /**
     * Matière de la session concernée
     */
    #[ORM\ManyToOne(targetEntity: SessionMatiere::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La matière est obligatoire.')]
    private ?SessionMatiere $sessionMatiere = null;

    /**
     * Salle de cours
     */
    #[ORM\ManyToOne(targetEntity: Salle::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La salle est obligatoire.')]
    private ?Salle $salle = null;

    /**
     * Formateurs intervenant sur ce créneau
     * Peut inclure plusieurs formateurs pour la co-intervention
     * 
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'creneau_recurrent_formateur')]
    #[Assert\Count(min: 1, minMessage: 'Au moins un formateur doit être assigné.')]
    private Collection $formateurs;

    /**
     * Jour de la semaine
     */
    #[ORM\Column(type: 'smallint', enumType: JourSemaine::class)]
    #[Assert\NotNull(message: 'Le jour de la semaine est obligatoire.')]
    private ?JourSemaine $jourSemaine = null;

    /**
     * Heure de début du créneau
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire.")]
    private ?\DateTimeInterface $heureDebut = null;

    /**
     * Heure de fin du créneau
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire.")]
    private ?\DateTimeInterface $heureFin = null;

    /**
     * Date de début de la période de récurrence
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTimeInterface $dateDebut = null;

    /**
     * Date de fin de la période de récurrence
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure à la date de début.'
    )]
    private ?\DateTimeInterface $dateFin = null;

    /**
     * Type de semaine (null = toutes les semaines, A = impaires, B = paires)
     */
    #[ORM\Column(type: 'string', length: 1, nullable: true, enumType: SemaineType::class)]
    private ?SemaineType $semaineType = null;

    /**
     * Créneau actif
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Commentaire libre
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

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

    /**
     * Séances générées depuis ce créneau
     * 
     * @var Collection<int, SeancePlanifiee>
     */
    #[ORM\OneToMany(targetEntity: SeancePlanifiee::class, mappedBy: 'creneauRecurrent')]
    #[ORM\OrderBy(['date' => 'ASC'])]
    private Collection $seances;

    public function __construct()
    {
        $this->formateurs = new ArrayCollection();
        $this->seances = new ArrayCollection();
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

    public function getJourSemaine(): ?JourSemaine
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(JourSemaine $jourSemaine): static
    {
        $this->jourSemaine = $jourSemaine;
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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getSemaineType(): ?SemaineType
    {
        return $this->semaineType;
    }

    public function setSemaineType(?SemaineType $semaineType): static
    {
        $this->semaineType = $semaineType;
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

    /**
     * @return Collection<int, SeancePlanifiee>
     */
    public function getSeances(): Collection
    {
        return $this->seances;
    }

    // ========================================
    // MÉTHODES MÉTIER
    // ========================================

    /**
     * Calcule la durée du créneau en minutes
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
     * Calcule la durée du créneau en heures (décimales)
     */
    public function getDureeHeures(): float
    {
        return round($this->getDureeMinutes() / 60, 2);
    }

    /**
     * Retourne la plage horaire formatée (ex: "08:00 - 10:00")
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
     * Retourne la période formatée (ex: "02/09/2024 - 30/06/2025")
     */
    public function getPeriodeFormatee(): string
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return '';
        }
        return sprintf(
            '%s - %s',
            $this->dateDebut->format('d/m/Y'),
            $this->dateFin->format('d/m/Y')
        );
    }

    /**
     * Retourne le libellé de la récurrence
     */
    public function getRecurrenceLibelle(): string
    {
        $libelle = $this->jourSemaine?->getLibelle() ?? '';
        
        if ($this->semaineType !== null) {
            $libelle .= ' (' . $this->semaineType->getLibelleCourt() . ')';
        }
        
        return $libelle;
    }

    /**
     * Vérifie si une date correspond à ce créneau (jour + semaine A/B)
     */
    public function correspondADate(\DateTimeInterface $date): bool
    {
        // Vérifier le jour de la semaine
        $jourIso = (int) $date->format('N');
        if ($this->jourSemaine?->value !== $jourIso) {
            return false;
        }
        
        // Vérifier la semaine A/B si définie
        if ($this->semaineType !== null) {
            return $this->semaineType->correspondA($date);
        }
        
        return true;
    }

    /**
     * Compte le nombre de séances générées
     */
    public function getNombreSeances(): int
    {
        return $this->seances->count();
    }

    /**
     * Compte le nombre de séances non modifiées manuellement
     */
    public function getNombreSeancesNonModifiees(): int
    {
        return $this->seances->filter(
            fn(SeancePlanifiee $s) => !$s->isModifieeDepuisCreneau()
        )->count();
    }

    /**
     * Retourne les noms des formateurs (séparés par virgule)
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

    // ========================================
    // VALIDATIONS
    // ========================================

    /**
     * Validation : heureDebut < heureFin
     */
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

    /**
     * Validation : la matière doit appartenir à la session
     */
    #[Assert\Callback]
    public function validateMatiereSession(ExecutionContextInterface $context): void
    {
        if ($this->session && $this->sessionMatiere) {
            if ($this->sessionMatiere->getSession()?->getId() !== $this->session->getId()) {
                $context->buildViolation('La matière sélectionnée ne fait pas partie de cette session.')
                    ->atPath('sessionMatiere')
                    ->addViolation();
            }
        }
    }

    /**
     * Validation : les formateurs doivent être assignés à la session
     */
    #[Assert\Callback]
    public function validateFormateursSession(ExecutionContextInterface $context): void
    {
        if ($this->session && !$this->formateurs->isEmpty()) {
            $formateursSession = $this->session->getFormateurs();
            foreach ($this->formateurs as $formateur) {
                if (!$formateursSession->contains($formateur)) {
                    $context->buildViolation(sprintf(
                        'Le formateur "%s" n\'est pas assigné à cette session.',
                        $formateur->getNomComplet()
                    ))
                        ->atPath('formateurs')
                        ->addViolation();
                }
            }
        }
    }

    /**
     * Validation : capacité de la salle suffisante (sauf virtuel)
     */
    #[Assert\Callback]
    public function validateCapaciteSalle(ExecutionContextInterface $context): void
    {
        if ($this->salle && $this->session) {
            if (!$this->salle->isVirtuel() && $this->salle->getCapacite() !== null) {
                $effectifMax = $this->session->getEffectifMax();
                if ($effectifMax !== null && $this->salle->getCapacite() < $effectifMax) {
                    $context->buildViolation(sprintf(
                        'La capacité de la salle (%d) est insuffisante pour l\'effectif maximum de la session (%d).',
                        $this->salle->getCapacite(),
                        $effectifMax
                    ))
                        ->atPath('salle')
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
        $jour = $this->jourSemaine?->getLibelleCourt() ?? '?';
        $horaire = $this->getPlageHoraire() ?: '?';
        
        return sprintf('%s - %s %s', $matiere, $jour, $horaire);
    }
}
