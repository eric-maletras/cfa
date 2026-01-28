<?php

namespace App\Enum;

/**
 * Enum des jours de la semaine pour les créneaux récurrents
 * 
 * Valeurs alignées sur ISO-8601 (1=lundi, 7=dimanche)
 * Note : Dimanche (7) non inclus car le CFA ne fait pas cours le dimanche
 */
enum JourSemaine: int
{
    case LUNDI = 1;
    case MARDI = 2;
    case MERCREDI = 3;
    case JEUDI = 4;
    case VENDREDI = 5;
    case SAMEDI = 6;

    /**
     * Retourne le libellé du jour
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
        };
    }

    /**
     * Retourne le libellé court (3 lettres)
     */
    public function getLibelleCourt(): string
    {
        return match ($this) {
            self::LUNDI => 'Lun',
            self::MARDI => 'Mar',
            self::MERCREDI => 'Mer',
            self::JEUDI => 'Jeu',
            self::VENDREDI => 'Ven',
            self::SAMEDI => 'Sam',
        };
    }

    /**
     * Retourne la couleur associée (pour l'UI)
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::LUNDI => '#3498db',    // Bleu
            self::MARDI => '#27ae60',    // Vert
            self::MERCREDI => '#9b59b6', // Violet
            self::JEUDI => '#e67e22',    // Orange
            self::VENDREDI => '#e74c3c', // Rouge
            self::SAMEDI => '#95a5a6',   // Gris
        };
    }

    /**
     * Vérifie si c'est un jour de week-end
     */
    public function isWeekend(): bool
    {
        return $this === self::SAMEDI;
    }

    /**
     * Retourne tous les jours ouvrables (lundi à vendredi)
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

    /**
     * Retourne tous les jours avec samedi
     * 
     * @return self[]
     */
    public static function tousLesJours(): array
    {
        return self::cases();
    }

    /**
     * Crée depuis un numéro ISO (1-6)
     */
    public static function fromIso(int $iso): ?self
    {
        return match ($iso) {
            1 => self::LUNDI,
            2 => self::MARDI,
            3 => self::MERCREDI,
            4 => self::JEUDI,
            5 => self::VENDREDI,
            6 => self::SAMEDI,
            default => null,
        };
    }

    /**
     * Retourne les choix pour un formulaire Symfony
     * 
     * @return array<string, self>
     */
    public static function getFormChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLibelle()] = $case;
        }
        return $choices;
    }
}
