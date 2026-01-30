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

/**
 * Représente un créneau horaire récurrent (modèle)
 * 
 * Un créneau définit une plage horaire qui se répète chaque semaine
 * sur un jour donné. Il sert de modèle pour générer les séances planifiées.
 */
#[ORM\Entity(repositoryClass: CreneauRecurrentRepository::class)]
#[ORM\Table(name: 'creneau_recurrent')]
class CreneauRecurrent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Session à laquelle appartient ce créneau
     */
    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La session est obligatoire')]
    private ?Session $session = null;

    /**
     * Jour de la semaine (1=lundi, 7=dimanche)
     */
    #[ORM\Column(type: 'integer', enumType: JourSemaine::class)]
    #[Assert\NotNull(message: 'Le jour de la semaine est obligatoire')]
    private ?JourSemaine $jourSemaine = null;

    /**
     * Type de semaine (A, B ou null pour toutes)
     */
    #[ORM\Column(type: 'string', length: 1, nullable: true, enumType: SemaineType::class)]
    private ?SemaineType $semaineType = null;

    /**
     * Heure de début du créneau
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire")]
    private ?\DateTimeInterface $heureDebut = null;

    /**
     * Heure de fin du créneau
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire")]
    #[Assert\GreaterThan(propertyPath: 'heureDebut', message: "L'heure de fin doit être après l'heure de début")]
    private ?\DateTimeInterface $heureFin = null;

    /**
     * Date de début de validité du créneau
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire')]
    private ?\DateTimeInterface $dateDebut = null;

    /**
     * Date de fin de validité du créneau
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateDebut', message: 'La date de fin doit être après la date de début')]
    private ?\DateTimeInterface $dateFin = null;

    /**
     * Salle assignée (optionnel)
     */
    #[ORM\ManyToOne(targetEntity: Salle::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Salle $salle = null;

    /**
     * Matière de la session (lien vers SessionMatiere)
     */
    #[ORM\ManyToOne(targetEntity: SessionMatiere::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SessionMatiere $sessionMatiere = null;

    /**
     * Formateurs assignés à ce créneau
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'creneau_recurrent_formateur')]
    private Collection $formateurs;

    /**
     * Indique si le créneau est actif
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $actif = true;

    /**
     * Commentaire libre
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * Séances planifiées générées à partir de ce créneau
     */
    #[ORM\OneToMany(targetEntity: SeancePlanifiee::class, mappedBy: 'creneauRecurrent')]
    private Collection $seancesPlanifiees;

    public function __construct()
    {
        $this->formateurs = new ArrayCollection();
        $this->seancesPlanifiees = new ArrayCollection();
    }

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

    public function getJourSemaine(): ?JourSemaine
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(?JourSemaine $jourSemaine): static
    {
        $this->jourSemaine = $jourSemaine;
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

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
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

    public function getSessionMatiere(): ?SessionMatiere
    {
        return $this->sessionMatiere;
    }

    public function setSessionMatiere(?SessionMatiere $sessionMatiere): static
    {
        $this->sessionMatiere = $sessionMatiere;
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

    /**
     * @return Collection<int, SeancePlanifiee>
     */
    public function getSeancesPlanifiees(): Collection
    {
        return $this->seancesPlanifiees;
    }

    /**
     * Retourne la durée du créneau en minutes
     */
    public function getDureeMinutes(): int
    {
        if (!$this->heureDebut || !$this->heureFin) {
            return 0;
        }
        
        $diff = $this->heureDebut->diff($this->heureFin);
        return ($diff->h * 60) + $diff->i;
    }

    /**
     * Retourne le libellé formaté du créneau
     */
    public function getLibelle(): string
    {
        $parts = [];
        
        if ($this->jourSemaine) {
            $parts[] = $this->jourSemaine->getLibelle();
        }
        
        if ($this->heureDebut && $this->heureFin) {
            $parts[] = sprintf('%s-%s', 
                $this->heureDebut->format('H:i'),
                $this->heureFin->format('H:i')
            );
        }
        
        if ($this->sessionMatiere?->getMatiere()) {
            $parts[] = $this->sessionMatiere->getMatiere()->getCode();
        }
        
        return implode(' - ', array_filter($parts));
    }

    /**
     * Clone l'entité (pour duplication)
     */
    public function __clone()
    {
        $this->id = null;
        
        // Cloner la collection de formateurs
        $formateursCopy = new ArrayCollection();
        foreach ($this->formateurs as $formateur) {
            $formateursCopy->add($formateur);
        }
        $this->formateurs = $formateursCopy;
        
        // Nouvelle collection pour les séances
        $this->seancesPlanifiees = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getLibelle();
    }
}
