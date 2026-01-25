<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Formation - Certifications professionnelles gérées par le CFA
 * (BTS, Titres professionnels, CQP, etc.)
 */
#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formation')]
#[ORM\Index(columns: ['code_rncp'], name: 'idx_formation_rncp')]
#[ORM\Index(columns: ['actif'], name: 'idx_formation_actif')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Intitulé officiel de la formation
     */
    #[ORM\Column(length: 255)]
    private ?string $intitule = null;

    /**
     * Intitulé court pour affichage
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $intituleCourt = null;

    /**
     * Code RNCP (ex: "RNCP35340" pour BTS SIO)
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeRncp = null;

    /**
     * Niveau de qualification (relation vers table de référence)
     */
    #[ORM\ManyToOne(targetEntity: NiveauQualification::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NiveauQualification $niveauQualification = null;

    /**
     * Type de certification (relation vers table de référence)
     */
    #[ORM\ManyToOne(targetEntity: TypeCertification::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeCertification $typeCertification = null;

    /**
     * Codes NSF associés (relation ManyToMany)
     * Une formation peut avoir plusieurs spécialités
     */
    #[ORM\ManyToMany(targetEntity: CodeNSF::class, inversedBy: 'formations')]
    #[ORM\JoinTable(name: 'formation_code_nsf')]
    private Collection $codesNsf;

    /**
     * Codes ROME associés (relation ManyToMany)
     * Une formation vise plusieurs métiers
     */
    #[ORM\ManyToMany(targetEntity: CodeROME::class, inversedBy: 'formations')]
    #[ORM\JoinTable(name: 'formation_code_rome')]
    private Collection $codesRome;

    /**
     * Durée totale en heures
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $dureeHeures = null;

    /**
     * Durée en mois
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $dureeMois = null;

    /**
     * Nombre de crédits ECTS (si applicable)
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $ects = null;

    /**
     * Options ou spécialités disponibles
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $options = null;

    /**
     * Description de la formation
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Objectifs de la formation
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectifs = null;

    /**
     * Prérequis d'entrée
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $prerequis = null;

    /**
     * Débouchés métiers (texte libre)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $debouches = null;

    /**
     * Poursuites d'études possibles
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $poursuiteEtudes = null;

    /**
     * Date d'enregistrement au RNCP
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnregistrementRncp = null;

    /**
     * Date d'échéance de l'enregistrement RNCP
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEcheanceRncp = null;

    /**
     * Formation active (proposée actuellement par le CFA)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

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

    public function __construct()
    {
        $this->codesNsf = new ArrayCollection();
        $this->codesRome = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;
        return $this;
    }

    public function getIntituleCourt(): ?string
    {
        return $this->intituleCourt;
    }

    public function setIntituleCourt(?string $intituleCourt): static
    {
        $this->intituleCourt = $intituleCourt;
        return $this;
    }

    public function getCodeRncp(): ?string
    {
        return $this->codeRncp;
    }

    public function setCodeRncp(?string $codeRncp): static
    {
        $this->codeRncp = $codeRncp;
        return $this;
    }

    public function getNiveauQualification(): ?NiveauQualification
    {
        return $this->niveauQualification;
    }

    public function setNiveauQualification(?NiveauQualification $niveauQualification): static
    {
        $this->niveauQualification = $niveauQualification;
        return $this;
    }

    public function getTypeCertification(): ?TypeCertification
    {
        return $this->typeCertification;
    }

    public function setTypeCertification(?TypeCertification $typeCertification): static
    {
        $this->typeCertification = $typeCertification;
        return $this;
    }

    /**
     * @return Collection<int, CodeNSF>
     */
    public function getCodesNsf(): Collection
    {
        return $this->codesNsf;
    }

    public function addCodeNsf(CodeNSF $codeNsf): static
    {
        if (!$this->codesNsf->contains($codeNsf)) {
            $this->codesNsf->add($codeNsf);
        }
        return $this;
    }

    public function removeCodeNsf(CodeNSF $codeNsf): static
    {
        $this->codesNsf->removeElement($codeNsf);
        return $this;
    }

    /**
     * @return Collection<int, CodeROME>
     */
    public function getCodesRome(): Collection
    {
        return $this->codesRome;
    }

    public function addCodeRome(CodeROME $codeRome): static
    {
        if (!$this->codesRome->contains($codeRome)) {
            $this->codesRome->add($codeRome);
        }
        return $this;
    }

    public function removeCodeRome(CodeROME $codeRome): static
    {
        $this->codesRome->removeElement($codeRome);
        return $this;
    }

    public function getDureeHeures(): ?int
    {
        return $this->dureeHeures;
    }

    public function setDureeHeures(?int $dureeHeures): static
    {
        $this->dureeHeures = $dureeHeures;
        return $this;
    }

    public function getDureeMois(): ?int
    {
        return $this->dureeMois;
    }

    public function setDureeMois(?int $dureeMois): static
    {
        $this->dureeMois = $dureeMois;
        return $this;
    }

    public function getEcts(): ?int
    {
        return $this->ects;
    }

    public function setEcts(?int $ects): static
    {
        $this->ects = $ects;
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;
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

    public function getObjectifs(): ?string
    {
        return $this->objectifs;
    }

    public function setObjectifs(?string $objectifs): static
    {
        $this->objectifs = $objectifs;
        return $this;
    }

    public function getPrerequis(): ?string
    {
        return $this->prerequis;
    }

    public function setPrerequis(?string $prerequis): static
    {
        $this->prerequis = $prerequis;
        return $this;
    }

    public function getDebouches(): ?string
    {
        return $this->debouches;
    }

    public function setDebouches(?string $debouches): static
    {
        $this->debouches = $debouches;
        return $this;
    }

    public function getPoursuiteEtudes(): ?string
    {
        return $this->poursuiteEtudes;
    }

    public function setPoursuiteEtudes(?string $poursuiteEtudes): static
    {
        $this->poursuiteEtudes = $poursuiteEtudes;
        return $this;
    }

    public function getDateEnregistrementRncp(): ?\DateTimeInterface
    {
        return $this->dateEnregistrementRncp;
    }

    public function setDateEnregistrementRncp(?\DateTimeInterface $dateEnregistrementRncp): static
    {
        $this->dateEnregistrementRncp = $dateEnregistrementRncp;
        return $this;
    }

    public function getDateEcheanceRncp(): ?\DateTimeInterface
    {
        return $this->dateEcheanceRncp;
    }

    public function setDateEcheanceRncp(?\DateTimeInterface $dateEcheanceRncp): static
    {
        $this->dateEcheanceRncp = $dateEcheanceRncp;
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Met à jour la date de modification avant persistence
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Représentation textuelle
     */
    public function __toString(): string
    {
        return $this->intituleCourt ?? $this->intitule ?? '';
    }
}
