<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Session - Occurrence temporelle d'une formation
 * 
 * Une session représente une "promotion" ou "cohorte" d'apprentis
 * suivant une formation donnée sur une période définie.
 * Exemple : "BTS SIO SISR 2024-2026" est une session de la formation "BTS SIO option SISR"
 */
#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\Table(name: 'session')]
#[ORM\Index(columns: ['code'], name: 'idx_session_code')]
#[ORM\Index(columns: ['statut'], name: 'idx_session_statut')]
#[ORM\Index(columns: ['date_debut'], name: 'idx_session_date_debut')]
#[ORM\Index(columns: ['actif'], name: 'idx_session_actif')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['code'], message: 'Ce code de session existe déjà.')]
class Session
{
    // ========================================
    // CONSTANTES - Statuts possibles
    // ========================================
    
    public const STATUT_PLANIFIEE = 'planifiee';
    public const STATUT_INSCRIPTIONS_OUVERTES = 'inscriptions_ouvertes';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINEE = 'terminee';
    public const STATUT_ANNULEE = 'annulee';
    
    public const STATUTS = [
        self::STATUT_PLANIFIEE => 'Planifiée',
        self::STATUT_INSCRIPTIONS_OUVERTES => 'Inscriptions ouvertes',
        self::STATUT_EN_COURS => 'En cours',
        self::STATUT_TERMINEE => 'Terminée',
        self::STATUT_ANNULEE => 'Annulée',
    ];
    
    // ========================================
    // CONSTANTES - Modalités pédagogiques
    // ========================================
    
    public const MODALITE_PRESENTIEL = 'presentiel';
    public const MODALITE_DISTANCIEL = 'distanciel';
    public const MODALITE_MIXTE = 'mixte';
    
    public const MODALITES = [
        self::MODALITE_PRESENTIEL => 'Présentiel',
        self::MODALITE_DISTANCIEL => 'Distanciel',
        self::MODALITE_MIXTE => 'Mixte (hybride)',
    ];

    // ========================================
    // PROPRIÉTÉS
    // ========================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code unique de la session (ex: "BTSSIO-SISR-2024")
     * Permet une identification rapide et sans ambiguïté
     */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le code est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le code ne peut dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9\-]+$/',
        message: 'Le code ne peut contenir que des majuscules, chiffres et tirets.'
    )]
    private ?string $code = null;

    /**
     * Libellé complet de la session
     * Ex: "BTS SIO option SISR - Promotion 2024-2026"
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le libellé ne peut dépasser {{ limit }} caractères.')]
    private ?string $libelle = null;

    /**
     * Formation rattachée (relation ManyToOne)
     */
    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La formation est obligatoire.')]
    private ?Formation $formation = null;

    /**
     * Date de début de la session
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTimeInterface $dateDebut = null;

    /**
     * Date de fin de la session
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure à la date de début.'
    )]
    private ?\DateTimeInterface $dateFin = null;

    /**
     * Date d'ouverture des inscriptions
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutInscriptions = null;

    /**
     * Date de clôture des inscriptions
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebutInscriptions',
        message: 'La date de fin des inscriptions doit être postérieure à la date de début.',
        groups: ['inscription_dates']
    )]
    private ?\DateTimeInterface $dateFinInscriptions = null;

    /**
     * Effectif minimum pour maintenir la session
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive(message: 'L\'effectif minimum doit être positif.')]
    private ?int $effectifMin = null;

    /**
     * Effectif maximum (capacité d'accueil)
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive(message: 'L\'effectif maximum doit être positif.')]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'effectifMin',
        message: 'L\'effectif maximum doit être supérieur ou égal au minimum.'
    )]
    private ?int $effectifMax = null;

    /**
     * Modalité pédagogique (présentiel, distanciel, mixte)
     */
    #[ORM\Column(length: 20, options: ['default' => self::MODALITE_PRESENTIEL])]
    #[Assert\Choice(
        choices: [self::MODALITE_PRESENTIEL, self::MODALITE_DISTANCIEL, self::MODALITE_MIXTE],
        message: 'Modalité invalide.'
    )]
    private string $modalite = self::MODALITE_PRESENTIEL;

    /**
     * Lieu principal de formation (texte libre ou à remplacer par FK vers Site)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    /**
     * Statut de la session
     */
    #[ORM\Column(length: 30, options: ['default' => self::STATUT_PLANIFIEE])]
    #[Assert\Choice(
        choices: [
            self::STATUT_PLANIFIEE,
            self::STATUT_INSCRIPTIONS_OUVERTES,
            self::STATUT_EN_COURS,
            self::STATUT_TERMINEE,
            self::STATUT_ANNULEE
        ],
        message: 'Statut invalide.'
    )]
    private string $statut = self::STATUT_PLANIFIEE;

    /**
     * Responsable pédagogique de la session (formateur référent)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $responsable = null;

    /**
     * Formateurs intervenant sur cette session
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'session_formateur')]
    private Collection $formateurs;

    /**
     * Inscriptions à cette session
     * @var Collection<int, Inscription>
    */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'session', orphanRemoval: true)]
    #[ORM\OrderBy(['dateInscription' => 'ASC'])]
    private Collection $inscriptions;

    /**
     * Matières de cette session (copiées depuis FormationMatiere)
     * @var Collection<int, SessionMatiere>
     */
    #[ORM\OneToMany(targetEntity: SessionMatiere::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $sessionMatieres;

    /**
     * Commentaire interne (notes administratives)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * Couleur d'affichage (pour calendrier/planning)
     * Format hexadécimal sans # (ex: "3498db")
     */
    #[ORM\Column(length: 6, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9A-Fa-f]{6}$/',
        message: 'La couleur doit être au format hexadécimal (6 caractères).'
    )]
    private ?string $couleur = null;

    /**
     * Session active
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

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
        $this->formateurs = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
        $this->sessionMatieres = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // ========================================
    // GETTERS / SETTERS
    // ========================================

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
        $this->code = strtoupper($code);
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

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
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

    public function getDateDebutInscriptions(): ?\DateTimeInterface
    {
        return $this->dateDebutInscriptions;
    }

    public function setDateDebutInscriptions(?\DateTimeInterface $dateDebutInscriptions): static
    {
        $this->dateDebutInscriptions = $dateDebutInscriptions;
        return $this;
    }

    public function getDateFinInscriptions(): ?\DateTimeInterface
    {
        return $this->dateFinInscriptions;
    }

    public function setDateFinInscriptions(?\DateTimeInterface $dateFinInscriptions): static
    {
        $this->dateFinInscriptions = $dateFinInscriptions;
        return $this;
    }

    public function getEffectifMin(): ?int
    {
        return $this->effectifMin;
    }

    public function setEffectifMin(?int $effectifMin): static
    {
        $this->effectifMin = $effectifMin;
        return $this;
    }

    public function getEffectifMax(): ?int
    {
        return $this->effectifMax;
    }

    public function setEffectifMax(?int $effectifMax): static
    {
        $this->effectifMax = $effectifMax;
        return $this;
    }

    public function getModalite(): string
    {
        return $this->modalite;
    }

    public function setModalite(string $modalite): static
    {
        $this->modalite = $modalite;
        return $this;
    }

    public function getModaliteLibelle(): string
    {
        return self::MODALITES[$this->modalite] ?? $this->modalite;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;
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

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): static
    {
        $this->responsable = $responsable;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        // Nettoyer le # si présent
        if ($couleur !== null && str_starts_with($couleur, '#')) {
            $couleur = substr($couleur, 1);
        }
        $this->couleur = $couleur;
        return $this;
    }

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setSession($this);
        }
        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getSession() === $this) {
                $inscription->setSession(null);
            }
        }
        return $this;
    }

    /**
     * Retourne les inscriptions validées
     */
    public function getInscriptionsValidees(): Collection
    {
        return $this->inscriptions->filter(
            fn(Inscription $i) => $i->getStatut() === Inscription::STATUT_VALIDEE
        );
    }

    /**
     * Compte le nombre d'inscrits validés
     */
    public function getNombreInscrits(): int
    {
        return $this->getInscriptionsValidees()->count();
    }

    /**
     * Vérifie si la session est complète (effectif max atteint)
     */
    public function isComplete(): bool
    {
        if ($this->effectifMax === null) {
            return false;
        }
        return $this->getNombreInscrits() >= $this->effectifMax;
    }

    /**
     * Retourne le nombre de places restantes
     */
    public function getPlacesRestantes(): ?int
    {
        if ($this->effectifMax === null) {
            return null;
        }
        return max(0, $this->effectifMax - $this->getNombreInscrits());
    }

    // ========================================
    // GESTION DES MATIÈRES DE LA SESSION
    // ========================================

    /**
     * @return Collection<int, SessionMatiere>
     */
    public function getSessionMatieres(): Collection
    {
        return $this->sessionMatieres;
    }

    /**
     * Retourne uniquement les matières actives de la session
     * 
     * @return Collection<int, SessionMatiere>
     */
    public function getSessionMatieresActives(): Collection
    {
        return $this->sessionMatieres->filter(
            fn(SessionMatiere $sm) => $sm->isActif()
        );
    }

    public function addSessionMatiere(SessionMatiere $sessionMatiere): static
    {
        if (!$this->sessionMatieres->contains($sessionMatiere)) {
            $this->sessionMatieres->add($sessionMatiere);
            $sessionMatiere->setSession($this);
        }
        return $this;
    }

    public function removeSessionMatiere(SessionMatiere $sessionMatiere): static
    {
        if ($this->sessionMatieres->removeElement($sessionMatiere)) {
            if ($sessionMatiere->getSession() === $this) {
                $sessionMatiere->setSession(null);
            }
        }
        return $this;
    }

    /**
     * Initialise les matières de la session depuis le référentiel de la formation
     * 
     * Cette méthode copie les FormationMatiere en SessionMatiere.
     * À appeler lors de la création d'une nouvelle session.
     * 
     * @return int Nombre de matières initialisées
     */
    public function initMatieresFromFormation(): int
    {
        if ($this->formation === null) {
            return 0;
        }

        $count = 0;
        foreach ($this->formation->getFormationMatieres() as $fm) {
            // Vérifier que la matière n'est pas déjà dans la session
            $exists = $this->sessionMatieres->exists(
                fn($key, SessionMatiere $sm) => $sm->getMatiere()?->getId() === $fm->getMatiere()?->getId()
            );

            if (!$exists) {
                $sessionMatiere = new SessionMatiere();
                $sessionMatiere->initFromFormationMatiere($fm);
                $this->addSessionMatiere($sessionMatiere);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Vérifie si les matières ont été initialisées
     */
    public function hasMatieresInitialisees(): bool
    {
        return !$this->sessionMatieres->isEmpty();
    }

    /**
     * Calcule le volume horaire total du référentiel pour cette session
     */
    public function getVolumeHeuresReferentielTotal(): int
    {
        $total = 0;
        foreach ($this->sessionMatieres as $sm) {
            if ($sm->isActif()) {
                $total += $sm->getVolumeHeuresReferentiel() ?? 0;
            }
        }
        return $total;
    }

    /**
     * Calcule le volume horaire total planifié pour cette session
     */
    public function getVolumeHeuresPlanifieTotal(): int
    {
        $total = 0;
        foreach ($this->sessionMatieres as $sm) {
            if ($sm->isActif()) {
                $total += $sm->getVolumeHeuresPlanifie() ?? $sm->getVolumeHeuresReferentiel() ?? 0;
            }
        }
        return $total;
    }

    /**
     * Calcule le volume horaire total réalisé pour cette session
     */
    public function getVolumeHeuresRealiseTotal(): int
    {
        $total = 0;
        foreach ($this->sessionMatieres as $sm) {
            if ($sm->isActif()) {
                $total += $sm->getVolumeHeuresRealise() ?? 0;
            }
        }
        return $total;
    }

    /**
     * Calcule le pourcentage de réalisation global
     */
    public function getPourcentageRealisationGlobal(): ?float
    {
        $planifie = $this->getVolumeHeuresPlanifieTotal();
        if ($planifie <= 0) {
            return null;
        }
        $realise = $this->getVolumeHeuresRealiseTotal();
        return round(($realise / $planifie) * 100, 1);
    }

    /**
     * Retourne la couleur avec le #
     */
    public function getCouleurHex(): ?string
    {
        return $this->couleur ? '#' . $this->couleur : null;
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ========================================
    // MÉTHODES MÉTIER
    // ========================================

    /**
     * Vérifie si les inscriptions sont actuellement ouvertes
     */
    public function isInscriptionsOuvertes(): bool
    {
        if ($this->statut !== self::STATUT_INSCRIPTIONS_OUVERTES) {
            return false;
        }
        
        $now = new \DateTime();
        
        // Vérifier les dates si définies
        if ($this->dateDebutInscriptions !== null && $now < $this->dateDebutInscriptions) {
            return false;
        }
        
        if ($this->dateFinInscriptions !== null && $now > $this->dateFinInscriptions) {
            return false;
        }
        
        return true;
    }

    /**
     * Vérifie si la session est en cours
     */
    public function isEnCours(): bool
    {
        $now = new \DateTime();
        return $this->statut === self::STATUT_EN_COURS 
            && $now >= $this->dateDebut 
            && $now <= $this->dateFin;
    }

    /**
     * Vérifie si la session est terminée
     */
    public function isTerminee(): bool
    {
        return $this->statut === self::STATUT_TERMINEE 
            || (new \DateTime()) > $this->dateFin;
    }

    /**
     * Vérifie si la session est annulée
     */
    public function isAnnulee(): bool
    {
        return $this->statut === self::STATUT_ANNULEE;
    }

    /**
     * Calcule la durée de la session en jours
     */
    public function getDureeJours(): ?int
    {
        if ($this->dateDebut === null || $this->dateFin === null) {
            return null;
        }
        
        $interval = $this->dateDebut->diff($this->dateFin);
        return $interval->days;
    }

    /**
     * Calcule la durée de la session en mois
     */
    public function getDureeMois(): ?int
    {
        if ($this->dateDebut === null || $this->dateFin === null) {
            return null;
        }
        
        $interval = $this->dateDebut->diff($this->dateFin);
        return ($interval->y * 12) + $interval->m;
    }

    /**
     * Retourne l'année de début (pour regroupement)
     */
    public function getAnneeDebut(): ?int
    {
        return $this->dateDebut?->format('Y');
    }

    /**
     * Retourne la période au format "Sept 2024 - Juil 2026"
     */
    public function getPeriodeFormatee(): string
    {
        if ($this->dateDebut === null || $this->dateFin === null) {
            return '';
        }
        
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            'MMM yyyy'
        );
        
        return ucfirst($formatter->format($this->dateDebut)) 
            . ' - ' 
            . ucfirst($formatter->format($this->dateFin));
    }

    /**
     * Génère automatiquement un code à partir de la formation et de l'année
     */
    public function generateCode(): string
    {
        if ($this->formation === null || $this->dateDebut === null) {
            return '';
        }
        
        // Prendre le code court de la formation ou générer depuis l'intitulé
        $formationCode = $this->formation->getIntituleCourt() 
            ?? $this->generateFormationCode($this->formation->getIntitule());
        
        // Nettoyer et formater
        $formationCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $formationCode));
        $annee = $this->dateDebut->format('Y');
        
        return substr($formationCode, 0, 20) . '-' . $annee;
    }

    /**
     * Génère un code court depuis un intitulé long
     */
    private function generateFormationCode(string $intitule): string
    {
        // Prendre les initiales des mots significatifs
        $words = preg_split('/[\s\-]+/', $intitule);
        $code = '';
        
        $stopWords = ['de', 'du', 'des', 'la', 'le', 'les', 'en', 'et', 'à', 'au', 'aux'];
        
        foreach ($words as $word) {
            if (!in_array(strtolower($word), $stopWords) && strlen($word) > 1) {
                $code .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return $code ?: 'FORM';
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
        return $this->libelle ?? $this->code ?? '';
    }
}
