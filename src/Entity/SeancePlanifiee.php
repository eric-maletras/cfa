<?php

namespace App\Entity;

use App\Enum\StatutSeance;
use App\Repository\SeancePlanifieeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente une séance planifiée dans le calendrier
 * 
 * Une séance peut être :
 * - Générée automatiquement à partir d'un créneau récurrent
 * - Créée manuellement
 * - Modifiée après génération (marquée comme modifiée)
 */
#[ORM\Entity(repositoryClass: SeancePlanifieeRepository::class)]
#[ORM\Table(name: 'seance_planifiee')]
#[ORM\Index(name: 'idx_seance_date', columns: ['date'])]
#[ORM\Index(name: 'idx_seance_session', columns: ['session_id'])]
class SeancePlanifiee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire")]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire")]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La session est obligatoire')]
    private ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: SessionMatiere::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SessionMatiere $sessionMatiere = null;

    #[ORM\ManyToOne(targetEntity: Salle::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Salle $salle = null;

    /**
     * Lien vers le créneau récurrent source (si généré automatiquement)
     */
    #[ORM\ManyToOne(targetEntity: CreneauRecurrent::class, inversedBy: 'seancesPlanifiees')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CreneauRecurrent $creneauRecurrent = null;

    /**
     * Formateurs assignés à cette séance
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'seance_planifiee_formateur')]
    private Collection $formateurs;

    /**
     * Statut de la séance
     */
    #[ORM\Column(type: 'string', length: 20, enumType: StatutSeance::class)]
    private StatutSeance $statut = StatutSeance::PLANIFIEE;

    /**
     * Indique si la séance a été modifiée manuellement après génération
     * Si true, elle ne sera pas supprimée lors d'une régénération
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $modifieeDepuisCreneau = false;

    /**
     * Commentaire libre pour cette séance
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->formateurs = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreneauRecurrent(): ?CreneauRecurrent
    {
        return $this->creneauRecurrent;
    }

    public function setCreneauRecurrent(?CreneauRecurrent $creneauRecurrent): static
    {
        $this->creneauRecurrent = $creneauRecurrent;
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

    public function getStatut(): StatutSeance
    {
        return $this->statut;
    }

    public function setStatut(StatutSeance $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function isModifieeDepuisCreneau(): bool
    {
        return $this->modifieeDepuisCreneau;
    }

    public function setModifieeDepuisCreneau(bool $modifieeDepuisCreneau): static
    {
        $this->modifieeDepuisCreneau = $modifieeDepuisCreneau;
        $this->updatedAt = new \DateTime();
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
     * Retourne true si la séance a été générée à partir d'un créneau récurrent
     */
    public function isGeneree(): bool
    {
        return $this->creneauRecurrent !== null;
    }

    /**
     * Retourne la durée de la séance en minutes
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
     * Retourne le libellé formaté de la séance
     */
    public function getLibelle(): string
    {
        $parts = [];
        
        if ($this->sessionMatiere?->getMatiere()) {
            $parts[] = $this->sessionMatiere->getMatiere()->getCode();
        }
        
        $parts[] = $this->date?->format('d/m/Y') ?? '';
        $parts[] = sprintf('%s-%s', 
            $this->heureDebut?->format('H:i') ?? '',
            $this->heureFin?->format('H:i') ?? ''
        );
        
        return implode(' - ', array_filter($parts));
    }

    public function __toString(): string
    {
        return $this->getLibelle();
    }
}
