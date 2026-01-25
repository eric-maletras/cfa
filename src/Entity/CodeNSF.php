<?php

namespace App\Entity;

use App\Repository\CodeNSFRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de référence des codes NSF (Nomenclature des Spécialités de Formation)
 * Structure hiérarchique à 3 niveaux + niveau 4 (croisement avec fonctions)
 * Référentiel INSEE, décret n° 94-522 du 21 juin 1994
 */
#[ORM\Entity(repositoryClass: CodeNSFRepository::class)]
#[ORM\Table(name: 'ref_code_nsf')]
#[ORM\Index(columns: ['code'], name: 'idx_nsf_code')]
#[ORM\Index(columns: ['niveau'], name: 'idx_nsf_niveau')]
#[ORM\Index(columns: ['parent_id'], name: 'idx_nsf_parent')]
class CodeNSF
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code NSF (1 à 4 caractères selon le niveau)
     * Niveau 1: 1 chiffre (ex: "3")
     * Niveau 2: 2 chiffres (ex: "32")
     * Niveau 3: 3 chiffres (ex: "326")
     * Niveau 4: 3 chiffres + 1 lettre (ex: "326r")
     */
    #[ORM\Column(length: 10, unique: true)]
    private ?string $code = null;

    /**
     * Libellé de la spécialité
     */
    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * Niveau dans la hiérarchie (1=domaine, 2=sous-domaine, 3=groupe, 4=spécialité fine)
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $niveau = null;

    /**
     * Type de domaine pour les niveaux 1-2
     * disciplinaire, technico_prod, technico_services, dev_personnel
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $typeDomaine = null;

    /**
     * Lettre de fonction pour le niveau 4 (m, n, p, r, s, t, u, v, w)
     */
    #[ORM\Column(length: 1, nullable: true)]
    private ?string $codeFonction = null;

    /**
     * Libellé de la fonction (Conception, Études, Méthodes, Contrôle, Production, etc.)
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $libelleFonction = null;

    /**
     * Parent dans la hiérarchie (null si niveau 1)
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * Enfants dans la hiérarchie
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['code' => 'ASC'])]
    private Collection $enfants;

    /**
     * Description détaillée
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Indique si ce code est actif
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Formations associées à ce code NSF (relation ManyToMany)
     */
    #[ORM\ManyToMany(targetEntity: Formation::class, mappedBy: 'codesNsf')]
    private Collection $formations;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
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

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(int $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getTypeDomaine(): ?string
    {
        return $this->typeDomaine;
    }

    public function setTypeDomaine(?string $typeDomaine): static
    {
        $this->typeDomaine = $typeDomaine;
        return $this;
    }

    public function getCodeFonction(): ?string
    {
        return $this->codeFonction;
    }

    public function setCodeFonction(?string $codeFonction): static
    {
        $this->codeFonction = $codeFonction;
        return $this;
    }

    public function getLibelleFonction(): ?string
    {
        return $this->libelleFonction;
    }

    public function setLibelleFonction(?string $libelleFonction): static
    {
        $this->libelleFonction = $libelleFonction;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }
        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }
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
            $formation->addCodeNsf($this);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            $formation->removeCodeNsf($this);
        }
        return $this;
    }

    /**
     * Retourne le chemin complet (ex: "3 > 32 > 326 > 326r")
     */
    public function getCheminComplet(): string
    {
        $chemin = [$this->code . ' - ' . $this->libelle];
        $parent = $this->parent;
        
        while ($parent !== null) {
            array_unshift($chemin, $parent->getCode() . ' - ' . $parent->getLibelle());
            $parent = $parent->getParent();
        }
        
        return implode(' > ', $chemin);
    }

    /**
     * Représentation textuelle pour les formulaires
     */
    public function __toString(): string
    {
        return $this->code . ' - ' . $this->libelle;
    }
}
