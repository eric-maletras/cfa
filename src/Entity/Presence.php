<?php

namespace App\Entity;

use App\Enum\StatutPresence;
use App\Repository\PresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Presence - Représente la présence d'un apprenti à une séance
 * 
 * Contient le token unique pour la signature par email et l'horodatage de la signature.
 * 
 * IMPORTANT: La présence ne peut être validée QUE par signature électronique de l'apprenti.
 * Le formateur ne peut PAS valider manuellement une présence (exigence OPCO/légale).
 */
#[ORM\Entity(repositoryClass: PresenceRepository::class)]
#[ORM\Table(name: 'presence')]
#[ORM\Index(name: 'idx_presence_token', columns: ['token'])]
#[ORM\Index(name: 'idx_presence_statut', columns: ['statut'])]
#[ORM\Index(name: 'idx_presence_appel', columns: ['appel_id'])]
#[ORM\UniqueConstraint(name: 'unique_appel_apprenti', columns: ['appel_id', 'apprenti_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['appel', 'apprenti'],
    message: 'Cet apprenti a déjà une présence enregistrée pour cet appel.'
)]
class Presence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Appel associé
     */
    #[ORM\ManyToOne(targetEntity: Appel::class, inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'appel est obligatoire.')]
    private ?Appel $appel = null;

    /**
     * Apprenti concerné
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'apprenti est obligatoire.')]
    private ?User $apprenti = null;

    /**
     * Statut de la présence
     */
    #[ORM\Column(type: 'string', length: 20, enumType: StatutPresence::class)]
    private StatutPresence $statut = StatutPresence::ABSENT;

    /**
     * Token unique pour la signature par email
     * Format UUID v4
     */
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $token = null;

    /**
     * Date et heure de signature (si signé)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSignature = null;

    /**
     * Adresse IP de signature (pour traçabilité)
     */
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipSignature = null;

    /**
     * User-Agent du navigateur lors de la signature
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgentSignature = null;

    /**
     * Indique si le lien de signature a été envoyé par email
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $emailEnvoye = false;

    /**
     * Date d'envoi de l'email
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnvoiEmail = null;

    /**
     * Motif d'absence prédéfini (relation vers MotifAbsence)
     * Utilisé lors de la justification d'une absence
     */
    #[ORM\ManyToOne(targetEntity: MotifAbsence::class, inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MotifAbsence $motifAbsence = null;

    /**
     * Commentaire libre de justification (ancien champ motif_absence renommé)
     * Permet d'ajouter des précisions en plus du motif prédéfini
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireJustification = null;

    /**
     * Minutes de retard (si statut RETARD ou en attente avec retard pré-enregistré)
     * Calculé automatiquement par blocs de 15 minutes lors de la réouverture
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $minutesRetard = null;

    /**
     * Commentaire du formateur
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getAppel(): ?Appel
    {
        return $this->appel;
    }

    public function setAppel(?Appel $appel): static
    {
        $this->appel = $appel;
        return $this;
    }

    public function getApprenti(): ?User
    {
        return $this->apprenti;
    }

    public function setApprenti(?User $apprenti): static
    {
        $this->apprenti = $apprenti;
        return $this;
    }

    public function getStatut(): StatutPresence
    {
        return $this->statut;
    }

    public function setStatut(StatutPresence $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getDateSignature(): ?\DateTimeInterface
    {
        return $this->dateSignature;
    }

    public function setDateSignature(?\DateTimeInterface $dateSignature): static
    {
        $this->dateSignature = $dateSignature;
        return $this;
    }

    public function getIpSignature(): ?string
    {
        return $this->ipSignature;
    }

    public function setIpSignature(?string $ipSignature): static
    {
        $this->ipSignature = $ipSignature;
        return $this;
    }

    public function getUserAgentSignature(): ?string
    {
        return $this->userAgentSignature;
    }

    public function setUserAgentSignature(?string $userAgentSignature): static
    {
        $this->userAgentSignature = $userAgentSignature;
        return $this;
    }

    public function isEmailEnvoye(): bool
    {
        return $this->emailEnvoye;
    }

    public function setEmailEnvoye(bool $emailEnvoye): static
    {
        $this->emailEnvoye = $emailEnvoye;
        return $this;
    }

    public function getDateEnvoiEmail(): ?\DateTimeInterface
    {
        return $this->dateEnvoiEmail;
    }

    public function setDateEnvoiEmail(?\DateTimeInterface $dateEnvoiEmail): static
    {
        $this->dateEnvoiEmail = $dateEnvoiEmail;
        return $this;
    }

    public function getMotifAbsence(): ?MotifAbsence
    {
        return $this->motifAbsence;
    }

    public function setMotifAbsence(?MotifAbsence $motifAbsence): static
    {
        $this->motifAbsence = $motifAbsence;
        return $this;
    }

    public function getCommentaireJustification(): ?string
    {
        return $this->commentaireJustification;
    }

    public function setCommentaireJustification(?string $commentaireJustification): static
    {
        $this->commentaireJustification = $commentaireJustification;
        return $this;
    }

    public function getMinutesRetard(): ?int
    {
        return $this->minutesRetard;
    }

    public function setMinutesRetard(?int $minutesRetard): static
    {
        $this->minutesRetard = $minutesRetard;
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

    // ========================================
    // MÉTHODES MÉTIER
    // ========================================

    /**
     * Génère un token unique pour la signature
     */
    public function genererToken(): static
    {
        // UUID v4 format
        $this->token = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        return $this;
    }

    /**
     * Vérifie si la présence peut être signée
     * 
     * Conditions :
     * - Token valide
     * - Statut EN_ATTENTE
     * - Appel non clôturé
     * - Liens encore valides (non expirés)
     */
    public function peutEtreSignee(): bool
    {
        // Doit avoir un token
        if (!$this->token) {
            return false;
        }

        // Doit être en attente
        if ($this->statut !== StatutPresence::EN_ATTENTE) {
            return false;
        }

        // L'appel ne doit pas être clôturé
        if ($this->appel && $this->appel->isCloture()) {
            return false;
        }

        // Les liens doivent encore être valides
        if ($this->appel && !$this->appel->isLiensValides()) {
            return false;
        }

        return true;
    }

    /**
     * Signe la présence
     * 
     * Note: Le statut final (PRESENT ou RETARD) est déterminé par le service
     * en fonction de minutesRetard
     */
    public function signer(string $ip, string $userAgent): static
    {
        // Si retard pré-enregistré, marquer comme RETARD, sinon PRESENT
        if ($this->minutesRetard && $this->minutesRetard > 0) {
            $this->statut = StatutPresence::RETARD;
        } else {
            $this->statut = StatutPresence::PRESENT;
        }
        
        $this->dateSignature = new \DateTime();
        $this->ipSignature = $ip;
        $this->userAgentSignature = $userAgent;
        return $this;
    }

    /**
     * Marque comme absent
     */
    public function marquerAbsent(?string $commentaire = null): static
    {
        $this->statut = StatutPresence::ABSENT;
        $this->commentaireJustification = $commentaire;
        $this->token = null; // Pas besoin de token pour un absent
        return $this;
    }

    /**
     * Marque comme absence justifiée
     * 
     * Seule modification autorisée par le formateur/admin (pas de validation de présence)
     * 
     * @param MotifAbsence|null $motif Motif prédéfini (optionnel)
     * @param string|null $commentaire Commentaire libre (optionnel)
     */
    public function justifierAbsence(?MotifAbsence $motif = null, ?string $commentaire = null): static
    {
        $this->statut = StatutPresence::ABSENT_JUSTIFIE;
        $this->motifAbsence = $motif;
        $this->commentaireJustification = $commentaire;
        return $this;
    }

    /**
     * Marque comme retard
     * 
     * Appelé automatiquement lors de la signature si minutesRetard > 0
     */
    public function marquerRetard(int $minutes, ?string $ip = null, ?string $userAgent = null): static
    {
        $this->statut = StatutPresence::RETARD;
        $this->minutesRetard = $minutes;
        $this->dateSignature = new \DateTime();
        if ($ip) {
            $this->ipSignature = $ip;
        }
        if ($userAgent) {
            $this->userAgentSignature = $userAgent;
        }
        return $this;
    }

    /**
     * Marque comme non signé (délai expiré)
     */
    public function marquerNonSigne(): static
    {
        if ($this->statut === StatutPresence::EN_ATTENTE) {
            $this->statut = StatutPresence::NON_SIGNE;
        }
        return $this;
    }

    /**
     * Vérifie si l'apprenti a signé
     */
    public function aSigne(): bool
    {
        return $this->dateSignature !== null && 
               in_array($this->statut, [StatutPresence::PRESENT, StatutPresence::RETARD]);
    }

    /**
     * Vérifie si c'est un retardataire (retard pré-enregistré mais pas encore signé)
     */
    public function estRetardataire(): bool
    {
        return $this->minutesRetard !== null && 
               $this->minutesRetard > 0 && 
               $this->statut === StatutPresence::EN_ATTENTE;
    }

    /**
     * Vérifie si l'absence peut être justifiée
     */
    public function peutEtreJustifiee(): bool
    {
        return in_array($this->statut, [
            StatutPresence::ABSENT,
            StatutPresence::NON_SIGNE,
        ]);
    }

    /**
     * Retourne le libellé du motif (prédéfini ou commentaire)
     */
    public function getMotifLibelle(): ?string
    {
        if ($this->motifAbsence) {
            return $this->motifAbsence->getLibelle();
        }
        return $this->commentaireJustification;
    }

    /**
     * Retourne une description du statut
     */
    public function getDescription(): string
    {
        $desc = $this->statut->getLibelle();
        
        if ($this->dateSignature) {
            $desc .= sprintf(' (signé le %s)', $this->dateSignature->format('d/m/Y à H:i'));
        }
        
        if ($this->minutesRetard) {
            $desc .= sprintf(' - %d min de retard', $this->minutesRetard);
        }
        
        if ($this->motifAbsence) {
            $desc .= sprintf(' - Motif: %s', $this->motifAbsence->getLibelle());
        }
        
        return $desc;
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
            '%s - %s',
            $this->apprenti?->getNomComplet() ?? 'Inconnu',
            $this->statut->getLibelle()
        );
    }
}
