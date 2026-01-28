<?php

namespace App\Entity;

use App\Enum\TypeJourFerme;
use App\Repository\JourFermeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente un jour fermé (férié, vacances, pont, fermeture)
 * Lié à un CalendrierAnnee
 */
#[ORM\Entity(repositoryClass: JourFermeRepository::class)]
#[ORM\Table(name: 'jour_ferme')]
#[ORM\UniqueConstraint(name: 'unique_calendrier_date', columns: ['calendrier_id', 'date'])]
#[UniqueEntity(
    fields: ['calendrier', 'date'],
    message: 'Cette date est déjà enregistrée comme jour fermé pour ce calendrier.',
    errorPath: 'date'
)]
class JourFerme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Calendrier auquel appartient ce jour fermé
     */
    #[ORM\ManyToOne(targetEntity: CalendrierAnnee::class, inversedBy: 'joursFermes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le calendrier est obligatoire.')]
    private ?CalendrierAnnee $calendrier = null;

    /**
     * Date du jour fermé
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    private ?\DateTimeInterface $date = null;

    /**
     * Libellé descriptif (ex: "Toussaint", "Noël", "Pont de l'Ascension")
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $libelle = null;

    /**
     * Type de jour fermé
     */
    #[ORM\Column(type: 'string', length: 20, enumType: TypeJourFerme::class)]
    #[Assert\NotNull(message: 'Le type est obligatoire.')]
    private ?TypeJourFerme $type = null;

    public function __construct()
    {
        $this->type = TypeJourFerme::FERMETURE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalendrier(): ?CalendrierAnnee
    {
        return $this->calendrier;
    }

    public function setCalendrier(?CalendrierAnnee $calendrier): static
    {
        $this->calendrier = $calendrier;
        return $this;
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getType(): ?TypeJourFerme
    {
        return $this->type;
    }

    public function setType(TypeJourFerme $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Retourne le jour de la semaine (1=lundi, 7=dimanche)
     */
    public function getJourSemaine(): ?int
    {
        return $this->date ? (int) $this->date->format('N') : null;
    }

    /**
     * Retourne le nom du jour de la semaine en français
     */
    public function getNomJourSemaine(): ?string
    {
        if (!$this->date) {
            return null;
        }

        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $jours[$this->getJourSemaine()] ?? null;
    }

    /**
     * Vérifie si le jour tombe un week-end
     */
    public function isWeekend(): bool
    {
        $jour = $this->getJourSemaine();
        return $jour === 6 || $jour === 7;
    }

    /**
     * Retourne la date formatée en français
     */
    public function getDateFormatee(): string
    {
        if (!$this->date) {
            return '';
        }
        
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );
        
        return $formatter->format($this->date);
    }

    public function __toString(): string
    {
        if ($this->date && $this->libelle) {
            return sprintf('%s - %s', $this->date->format('d/m/Y'), $this->libelle);
        }
        return $this->libelle ?? 'Jour fermé';
    }
}
