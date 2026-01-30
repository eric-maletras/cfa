<?php

namespace App\Enum;

/**
 * Type de semaine (alternance A/B)
 */
enum SemaineType: string
{
    case A = 'A';
    case B = 'B';

    /**
     * Retourne le libellé complet
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::A => 'Semaine A',
            self::B => 'Semaine B',
        };
    }

    /**
     * Retourne la couleur associée pour l'affichage
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::A => '#1565c0', // Bleu
            self::B => '#2e7d32', // Vert
        };
    }

    /**
     * Retourne la classe CSS pour le badge
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::A => 'semaine-A',
            self::B => 'semaine-B',
        };
    }

    /**
     * Retourne l'autre type de semaine (alternance)
     */
    public function getAutre(): self
    {
        return match ($this) {
            self::A => self::B,
            self::B => self::A,
        };
    }
}
