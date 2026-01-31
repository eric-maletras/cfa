<?php

namespace App\Entity;

use App\Repository\AppelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Appel - Représente une session d'appel pour une séance
 * 
 * Un appel est créé lorsqu'un formateur décide de faire l'appel pour une séance.
 * Il contient toutes les présences associées et les métadonnées de l'appel.
 */
#[ORM\Entity(repositoryClass: AppelRepository::class)]
#[ORM\Table(name: 'appel')]
#[ORM\Index(name: 'idx_appel_seance', columns: ['seance_id'])]
#[ORM\Index(name: 'idx_appel_date', columns: ['date_appel'])]
#[ORM\HasLifecycleCallbacks]
class Appel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Séance concernée par l'appel
     */
    #[ORM\ManyToOne(targetEntity: SeancePlanifiee::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La séance est obligatoire.')]
    private ?SeancePlanifiee $seance = null;

    /**
     * Formateur ayant effectué l'appel
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le formateur est obligatoire.')]
    private ?User $formateur = null;

    /**
     * Date et heure de l'appel
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAppel = null;

    /**
     * Date et heure d'expiration des liens de signature
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateExpiration = null;

    /**
     * Indique si les emails ont été envoyés
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $emailsEnvoyes = false;

    /**
     * Date d'envoi des emails
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnvoiEmails = null;

    /**
     * Commentaire du formateur sur l'appel
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * Indique si l'appel est clôturé (plus de modifications possibles)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $cloture = false;

    /**
     * Date de clôture de l'appel
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCloture = null;

    /**
     * Présences associées à cet appel
     * @var Collection<int, Presence>
     */
    #[ORM\OneToMany(targetEntity: Presence::class, mappedBy: 'appel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $presences;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->presences = new ArrayCollection();
        $this->dateAppel = new \DateTime();
        $this->createdAt = new \DateTime();
        // Par défaut, expiration à la fin de la journée
        $this->dateExpiration = (new \DateTime())->setTime(23, 59, 59);
    }

    // ========================================
    // GETTERS / SETTERS
    // ========================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeance(): ?SeancePlanifiee
    {
        return $this->seance;
    }

    public function setSeance(?SeancePlanifiee $seance): static
    {
        $this->seance = $seance;
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

    public function getDateAppel(): ?\DateTimeInterface
    {
        return $this->dateAppel;
    }

    public function setDateAppel(\DateTimeInterface $dateAppel): static
    {
        $this->dateAppel = $dateAppel;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function isEmailsEnvoyes(): bool
    {
        return $this->emailsEnvoyes;
    }

    public function setEmailsEnvoyes(bool $emailsEnvoyes): static
    {
        $this->emailsEnvoyes = $emailsEnvoyes;
        return $this;
    }

    public function getDateEnvoiEmails(): ?\DateTimeInterface
    {
        return $this->dateEnvoiEmails;
    }

    public function setDateEnvoiEmails(?\DateTimeInterface $dateEnvoiEmails): static
    {
        $this->dateEnvoiEmails = $dateEnvoiEmails;
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

    public function isCloture(): bool
    {
        return $this->cloture;
    }

    public function setCloture(bool $cloture): static
    {
        $this->cloture = $cloture;
        return $this;
    }

    public function getDateCloture(): ?\DateTimeInterface
    {
        return $this->dateCloture;
    }

    public function setDateCloture(?\DateTimeInterface $dateCloture): static
    {
        $this->dateCloture = $dateCloture;
        return $this;
    }

    /**
     * @return Collection<int, Presence>
     */
    public function getPresences(): Collection
    {
        return $this->presences;
    }

    public function addPresence(Presence $presence): static
    {
        if (!$this->presences->contains($presence)) {
            $this->presences->add($presence);
            $presence->setAppel($this);
        }
        return $this;
    }

    public function removePresence(Presence $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            if ($presence->getAppel() === $this) {
                $presence->setAppel(null);
            }
        }
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
     * Vérifie si les liens de signature sont encore valides
     */
    public function isLiensValides(): bool
    {
        if ($this->cloture) {
            return false;
        }
        return new \DateTime() < $this->dateExpiration;
    }

    /**
     * Vérifie si l'appel peut être modifié
     */
    public function isModifiable(): bool
    {
        return !$this->cloture;
    }

    /**
     * Clôture l'appel
     */
    public function cloturer(): static
    {
        $this->cloture = true;
        $this->dateCloture = new \DateTime();
        return $this;
    }

    /**
     * Retourne le nombre de présents
     */
    public function getNbPresents(): int
    {
        return $this->presences->filter(
            fn(Presence $p) => $p->getStatut()->compteCommePresent()
        )->count();
    }

    /**
     * Retourne le nombre d'absents
     */
    public function getNbAbsents(): int
    {
        return $this->presences->filter(
            fn(Presence $p) => $p->getStatut()->compteCommeAbsent()
        )->count();
    }

    /**
     * Retourne le nombre d'attente de signature
     */
    public function getNbEnAttente(): int
    {
        return $this->presences->filter(
            fn(Presence $p) => $p->getStatut() === \App\Enum\StatutPresence::EN_ATTENTE
        )->count();
    }

    /**
     * Retourne le taux de présence en pourcentage
     */
    public function getTauxPresence(): float
    {
        $total = $this->presences->count();
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->getNbPresents() / $total) * 100, 1);
    }

    /**
     * Retourne les présences triées par nom d'apprenti
     */
    public function getPresencesTriees(): array
    {
        $presences = $this->presences->toArray();
        usort($presences, fn(Presence $a, Presence $b) => 
            strcmp($a->getApprenti()->getNom(), $b->getApprenti()->getNom())
        );
        return $presences;
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
        return sprintf(
            'Appel du %s - %s',
            $this->dateAppel?->format('d/m/Y H:i') ?? 'N/A',
            $this->seance?->getLibelle() ?? 'N/A'
        );
    }
}
