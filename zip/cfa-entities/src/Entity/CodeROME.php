<?php

namespace App\Entity;

use App\Repository\CodeROMERepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de référence des codes ROME (Répertoire Opérationnel des Métiers et des Emplois)
 * Géré par France Travail (ex-Pôle Emploi)
 * Structure : 1 lettre (domaine) + 4 chiffres
 * Version actuelle : ROME 4.0 (mars 2023)
 */
#[ORM\Entity(repositoryClass: CodeROMERepository::class)]
#[ORM\Table(name: 'ref_code_rome')]
#[ORM\Index(columns: ['code'], name: 'idx_rome_code')]
#[ORM\Index(columns: ['domaine_code'], name: 'idx_rome_domaine')]
class CodeROME
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code ROME complet (ex: "M1805")
     * Format : 1 lettre + 4 chiffres
     */
    #[ORM\Column(length: 5, unique: true)]
    private ?string $code = null;

    /**
     * Intitulé du métier/emploi (ex: "Études et développement informatique")
     */
    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * Code du grand domaine (A à N)
     */
    #[ORM\Column(length: 1)]
    private ?string $domaineCode = null;

    /**
     * Libellé du grand domaine (ex: "Support à l'entreprise")
     */
    #[ORM\Column(length: 150)]
    private ?string $domaineLibelle = null;

    /**
     * Code du domaine professionnel (2 chiffres, ex: "18" pour M18xx)
     */
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $sousDomaineCode = null;

    /**
     * Libellé du domaine professionnel
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $sousDomaineLibelle = null;

    /**
     * Définition du métier
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $definition = null;

    /**
     * Conditions d'accès au métier
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsAcces = null;

    /**
     * Version du ROME (ex: "4.0", "3.0")
     */
    #[ORM\Column(length: 10, options: ['default' => '4.0'])]
    private string $versionRome = '4.0';

    /**
     * Date de dernière mise à jour de la fiche
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateMaj = null;

    /**
     * Indique si ce code est actif
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Formations associées à ce code ROME (relation ManyToMany)
     */
    #[ORM\ManyToMany(targetEntity: Formation::class, mappedBy: 'codesRome')]
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

    public function getDomaineCode(): ?string
    {
        return $this->domaineCode;
    }

    public function setDomaineCode(string $domaineCode): static
    {
        $this->domaineCode = strtoupper($domaineCode);
        return $this;
    }

    public function getDomaineLibelle(): ?string
    {
        return $this->domaineLibelle;
    }

    public function setDomaineLibelle(string $domaineLibelle): static
    {
        $this->domaineLibelle = $domaineLibelle;
        return $this;
    }

    public function getSousDomaineCode(): ?string
    {
        return $this->sousDomaineCode;
    }

    public function setSousDomaineCode(?string $sousDomaineCode): static
    {
        $this->sousDomaineCode = $sousDomaineCode;
        return $this;
    }

    public function getSousDomaineLibelle(): ?string
    {
        return $this->sousDomaineLibelle;
    }

    public function setSousDomaineLibelle(?string $sousDomaineLibelle): static
    {
        $this->sousDomaineLibelle = $sousDomaineLibelle;
        return $this;
    }

    public function getDefinition(): ?string
    {
        return $this->definition;
    }

    public function setDefinition(?string $definition): static
    {
        $this->definition = $definition;
        return $this;
    }

    public function getConditionsAcces(): ?string
    {
        return $this->conditionsAcces;
    }

    public function setConditionsAcces(?string $conditionsAcces): static
    {
        $this->conditionsAcces = $conditionsAcces;
        return $this;
    }

    public function getVersionRome(): string
    {
        return $this->versionRome;
    }

    public function setVersionRome(string $versionRome): static
    {
        $this->versionRome = $versionRome;
        return $this;
    }

    public function getDateMaj(): ?\DateTimeInterface
    {
        return $this->dateMaj;
    }

    public function setDateMaj(?\DateTimeInterface $dateMaj): static
    {
        $this->dateMaj = $dateMaj;
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
            $formation->addCodeRome($this);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            $formation->removeCodeRome($this);
        }
        return $this;
    }

    /**
     * Retourne le code formaté avec le domaine (ex: "M1805 - Études et développement informatique")
     */
    public function getCodeComplet(): string
    {
        return $this->code . ' - ' . $this->libelle;
    }

    /**
     * Extrait le code du sous-domaine depuis le code complet
     */
    public function extractSousDomaineCode(): ?string
    {
        if ($this->code && strlen($this->code) >= 3) {
            return substr($this->code, 1, 2);
        }
        return null;
    }

    /**
     * Représentation textuelle pour les formulaires
     */
    public function __toString(): string
    {
        return $this->code . ' - ' . $this->libelle;
    }
}
