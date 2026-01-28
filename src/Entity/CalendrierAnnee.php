<?php

namespace App\Entity;

use App\Repository\CalendrierAnneeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente une année scolaire/calendrier du CFA
 * 
 * Exemple : 2025-2026 du 01/09/2025 au 31/08/2026
 */
#[ORM\Entity(repositoryClass: CalendrierAnneeRepository::class)]
#[ORM\Table(name: 'calendrier_annee')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['code'], message: 'Ce code de calendrier existe déjà.')]
class CalendrierAnnee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code unique du calendrier (ex: "2025-2026")
     */
    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Le code est obligatoire.')]
    #[Assert\Length(max: 20, maxMessage: 'Le code ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{4}$/',
        message: 'Le code doit être au format AAAA-AAAA (ex: 2025-2026).'
    )]
    private ?string $code = null;

    /**
     * Libellé descriptif (ex: "Année scolaire 2025-2026")
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $libelle = null;

    /**
     * Date de début de l'année scolaire
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTimeInterface $dateDebut = null;

    /**
     * Date de fin de l'année scolaire
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure à la date de début.'
    )]
    private ?\DateTimeInterface $dateFin = null;

    /**
     * Heure de début par défaut des journées (ex: 08:30)
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début par défaut est obligatoire.")]
    private ?\DateTimeInterface $heureDebutDefaut = null;

    /**
     * Heure de fin par défaut des journées (ex: 17:30)
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin par défaut est obligatoire.")]
    private ?\DateTimeInterface $heureFinDefaut = null;

    /**
     * Indique si ce calendrier est actif (un seul actif à la fois recommandé)
     */
    #[ORM\Column]
    private bool $actif = true;

    /**
     * Date de création
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Date de dernière modification
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Collection des jours fermés de ce calendrier
     * 
     * @var Collection<int, JourFerme>
     */
    #[ORM\OneToMany(
        targetEntity: JourFerme::class,
        mappedBy: 'calendrier',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['date' => 'ASC'])]
    private Collection $joursFermes;

    public function __construct()
    {
        $this->joursFermes = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        
        // Valeurs par défaut pour les heures
        $this->heureDebutDefaut = new \DateTime('08:30:00');
        $this->heureFinDefaut = new \DateTime('17:30:00');
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
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
        $this->code = $code;
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

    public function getHeureDebutDefaut(): ?\DateTimeInterface
    {
        return $this->heureDebutDefaut;
    }

    public function setHeureDebutDefaut(\DateTimeInterface $heureDebutDefaut): static
    {
        $this->heureDebutDefaut = $heureDebutDefaut;
        return $this;
    }

    public function getHeureFinDefaut(): ?\DateTimeInterface
    {
        return $this->heureFinDefaut;
    }

    public function setHeureFinDefaut(\DateTimeInterface $heureFinDefaut): static
    {
        $this->heureFinDefaut = $heureFinDefaut;
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

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, JourFerme>
     */
    public function getJoursFermes(): Collection
    {
        return $this->joursFermes;
    }

    public function addJourFerme(JourFerme $jourFerme): static
    {
        if (!$this->joursFermes->contains($jourFerme)) {
            $this->joursFermes->add($jourFerme);
            $jourFerme->setCalendrier($this);
        }
        return $this;
    }

    public function removeJourFerme(JourFerme $jourFerme): static
    {
        if ($this->joursFermes->removeElement($jourFerme)) {
            if ($jourFerme->getCalendrier() === $this) {
                $jourFerme->setCalendrier(null);
            }
        }
        return $this;
    }

    /**
     * Retourne le nombre de jours fermés
     */
    public function getNbJoursFermes(): int
    {
        return $this->joursFermes->count();
    }

    /**
     * Retourne les années civiles couvertes par ce calendrier
     * 
     * @return int[] Liste des années (ex: [2025, 2026])
     */
    public function getAnneesCouvertes(): array
    {
        $annees = [];
        if ($this->dateDebut && $this->dateFin) {
            $anneeDebut = (int) $this->dateDebut->format('Y');
            $anneeFin = (int) $this->dateFin->format('Y');
            for ($a = $anneeDebut; $a <= $anneeFin; $a++) {
                $annees[] = $a;
            }
        }
        return $annees;
    }

    /**
     * Vérifie si une date est dans la période du calendrier
     */
    public function contientDate(\DateTimeInterface $date): bool
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return false;
        }
        return $date >= $this->dateDebut && $date <= $this->dateFin;
    }

    /**
     * Génère automatiquement le libellé à partir du code
     */
    public function genererLibelle(): string
    {
        return sprintf('Année scolaire %s', $this->code ?? '');
    }

    public function __toString(): string
    {
        return $this->libelle ?? $this->code ?? 'Nouveau calendrier';
    }
}
