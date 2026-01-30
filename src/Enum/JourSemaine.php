<?php

namespace App\Enum;

/**
 * Jours de la semaine (valeurs ISO : 1=lundi, 7=dimanche)
 */
enum JourSemaine: int
{
    case LUNDI = 1;
    case MARDI = 2;
    case MERCREDI = 3;
    case JEUDI = 4;
    case VENDREDI = 5;
    case SAMEDI = 6;
    case DIMANCHE = 7;

    /**
     * Retourne le libellé en français avec majuscule
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::LUNDI => 'Lundi',
            self::MARDI => 'Mardi',
            self::MERCREDI => 'Mercredi',
            self::JEUDI => 'Jeudi',
            self::VENDREDI => 'Vendredi',
            self::SAMEDI => 'Samedi',
            self::DIMANCHE => 'Dimanche',
        };
    }

    /**
     * Retourne le libellé abrégé (3 lettres)
     */
    public function getLibelleAbrege(): string
    {
        return match ($this) {
            self::LUNDI => 'Lun',
            self::MARDI => 'Mar',
            self::MERCREDI => 'Mer',
            self::JEUDI => 'Jeu',
            self::VENDREDI => 'Ven',
            self::SAMEDI => 'Sam',
            self::DIMANCHE => 'Dim',
        };
    }

    /**
     * Retourne le numéro ISO du jour (1=lundi, 7=dimanche)
     */
    public function getNumero(): int
    {
        return $this->value;
    }

    /**
     * Crée un JourSemaine à partir du numéro ISO
     */
    public static function fromNumero(int $numero): self
    {
        return self::from($numero);
    }

    /**
     * Indique si c'est un jour ouvrable (lundi à vendredi)
     */
    public function isOuvrable(): bool
    {
        return $this->value <= 5;
    }

    /**
     * Retourne la liste des jours ouvrables
     * 
     * @return self[]
     */
    public static function joursOuvrables(): array
    {
        return [
            self::LUNDI,
            self::MARDI,
            self::MERCREDI,
            self::JEUDI,
            self::VENDREDI,
        ];
    }
}
