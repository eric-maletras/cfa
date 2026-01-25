<?php

namespace App\Entity;

use App\Repository\TypeCertificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de référence des types de certifications professionnelles
 * Distingue les diplômes d'État, titres professionnels, CQP, etc.
 */
#[ORM\Entity(repositoryClass: TypeCertificationRepository::class)]
#[ORM\Table(name: 'ref_type_certification')]
#[ORM\Index(columns: ['code'], name: 'idx_type_cert_code')]
class TypeCertification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code court (ex: "BTS", "TP", "CQP", "DE")
     */
    #[ORM\Column(length: 20, unique: true)]
    private ?string $code = null;

    /**
     * Libellé complet (ex: "Brevet de technicien supérieur")
     */
    #[ORM\Column(length: 150)]
    private ?string $libelle = null;

    /**
     * Libellé abrégé pour affichage (ex: "BTS")
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $libelleAbrege = null;

    /**
     * Type de certificateur (ministere, branche, organisme_prive, consulaire)
     */
    #[ORM\Column(length: 30)]
    private ?string $certificateurType = null;

    /**
     * Ministère ou organisme certificateur principal
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $certificateurNom = null;

    /**
     * Mode d'enregistrement au RNCP (de_droit, sur_demande, optionnel, non_applicable)
     */
    #[ORM\Column(length: 20)]
    private ?string $enregistrementRncp = null;

    /**
     * Peut être préparé en apprentissage
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $apprentissagePossible = true;

    /**
     * Peut être obtenu par VAE
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $vaePossible = true;

    /**
     * Description détaillée du type de certification
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Ordre d'affichage dans les listes
     */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $ordreAffichage = 0;

    /**
     * Indique si ce type est actif
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Formations associées à ce type
     */
    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'typeCertification')]
    private Collection $formations;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
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

    public function getLibelleAbrege(): ?string
    {
        return $this->libelleAbrege;
    }

    public function setLibelleAbrege(?string $libelleAbrege): static
    {
        $this->libelleAbrege = $libelleAbrege;
        return $this;
    }

    public function getCertificateurType(): ?string
    {
        return $this->certificateurType;
    }

    public function setCertificateurType(string $certificateurType): static
    {
        $this->certificateurType = $certificateurType;
        return $this;
    }

    public function getCertificateurNom(): ?string
    {
        return $this->certificateurNom;
    }

    public function setCertificateurNom(?string $certificateurNom): static
    {
        $this->certificateurNom = $certificateurNom;
        return $this;
    }

    public function getEnregistrementRncp(): ?string
    {
        return $this->enregistrementRncp;
    }

    public function setEnregistrementRncp(string $enregistrementRncp): static
    {
        $this->enregistrementRncp = $enregistrementRncp;
        return $this;
    }

    public function isApprentissagePossible(): bool
    {
        return $this->apprentissagePossible;
    }

    public function setApprentissagePossible(bool $apprentissagePossible): static
    {
        $this->apprentissagePossible = $apprentissagePossible;
        return $this;
    }

    public function isVaePossible(): bool
    {
        return $this->vaePossible;
    }

    public function setVaePossible(bool $vaePossible): static
    {
        $this->vaePossible = $vaePossible;
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

    public function getOrdreAffichage(): int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(int $ordreAffichage): static
    {
        $this->ordreAffichage = $ordreAffichage;
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

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setTypeCertification($this);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getTypeCertification() === $this) {
                $formation->setTypeCertification(null);
            }
        }
        return $this;
    }

    /**
     * Représentation textuelle pour les formulaires
     */
    public function __toString(): string
    {
        return $this->libelleAbrege ?? $this->libelle ?? '';
    }
}
