<?php

namespace App\Entity;

use App\Repository\NiveauQualificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de référence des niveaux de qualification
 * Cadre national des certifications professionnelles (Décret n° 2019-14 du 8 janvier 2019)
 * 8 niveaux alignés sur le Cadre européen des certifications (CEC)
 */
#[ORM\Entity(repositoryClass: NiveauQualificationRepository::class)]
#[ORM\Table(name: 'ref_niveau_qualification')]
#[ORM\Index(columns: ['code'], name: 'idx_niveau_code')]
class NiveauQualification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code du niveau (1 à 8)
     */
    #[ORM\Column(type: Types::SMALLINT, unique: true)]
    private ?int $code = null;

    /**
     * Libellé court (ex: "Niveau 5 - BTS/DUT")
     */
    #[ORM\Column(length: 100)]
    private ?string $libelle = null;

    /**
     * Équivalent diplôme principal (ex: "BTS, DUT, DEUST")
     */
    #[ORM\Column(length: 255)]
    private ?string $equivalentDiplome = null;

    /**
     * Description des compétences attendues selon le cadre national
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Correspondance avec l'ancienne nomenclature de 1969 (V, IV, III, II, I)
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $ancienNiveau = null;

    /**
     * Niveau européen CEC correspondant (identique au code pour la France)
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $niveauCec = null;

    /**
     * Indique si ce niveau est actif (permet de désactiver sans supprimer)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $actif = true;

    /**
     * Formations associées à ce niveau
     */
    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'niveauQualification')]
    private Collection $formations;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(int $code): static
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

    public function getEquivalentDiplome(): ?string
    {
        return $this->equivalentDiplome;
    }

    public function setEquivalentDiplome(string $equivalentDiplome): static
    {
        $this->equivalentDiplome = $equivalentDiplome;
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

    public function getAncienNiveau(): ?string
    {
        return $this->ancienNiveau;
    }

    public function setAncienNiveau(?string $ancienNiveau): static
    {
        $this->ancienNiveau = $ancienNiveau;
        return $this;
    }

    public function getNiveauCec(): ?int
    {
        return $this->niveauCec;
    }

    public function setNiveauCec(?int $niveauCec): static
    {
        $this->niveauCec = $niveauCec;
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
            $formation->setNiveauQualification($this);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getNiveauQualification() === $this) {
                $formation->setNiveauQualification(null);
            }
        }
        return $this;
    }

    /**
     * Représentation textuelle pour les formulaires
     */
    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}
