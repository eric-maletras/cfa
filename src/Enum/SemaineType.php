<?php

namespace App\Enum;

/**
 * Enum du type de semaine pour les créneaux récurrents
 * 
 * Permet de définir si un créneau a lieu :
 * - Toutes les semaines (null)
 * - Semaines A (impaires ISO)
 * - Semaines B (paires ISO)
 * 
 * Exemple d'utilisation : cours en alternance pour des groupes différents
 */
enum SemaineType: string
{
    case A = 'A';  // Semaines impaires ISO (1, 3, 5, ...)
    case B = 'B';  // Semaines paires ISO (2, 4, 6, ...)

    /**
     * Retourne le libellé
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::A => 'Semaine A (impaires)',
            self::B => 'Semaine B (paires)',
        };
    }

    /**
     * Retourne le libellé court
     */
    public function getLibelleCourt(): string
    {
        return match ($this) {
            self::A => 'Sem. A',
            self::B => 'Sem. B',
        };
    }

    /**
     * Retourne la couleur associée (pour l'UI)
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::A => '#3498db', // Bleu
            self::B => '#9b59b6', // Violet
        };
    }

    /**
     * Retourne la classe CSS pour badge
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::A => 'info',
            self::B => 'secondary',
        };
    }

    /**
     * Vérifie si une date correspond à ce type de semaine
     */
    public function correspondA(\DateTimeInterface $date): bool
    {
        $semaineIso = (int) $date->format('W');
        $estImpaire = $semaineIso % 2 === 1;

        return match ($this) {
            self::A => $estImpaire,
            self::B => !$estImpaire,
        };
    }

    /**
     * Retourne le type de semaine pour une date donnée
     */
    public static function pourDate(\DateTimeInterface $date): self
    {
        $semaineIso = (int) $date->format('W');
        return ($semaineIso % 2 === 1) ? self::A : self::B;
    }

    /**
     * Retourne les choix pour un formulaire Symfony
     * 
     * @return array<string, self>
     */
    public static function getFormChoices(): array
    {
        return [
            'Semaine A (impaires)' => self::A,
            'Semaine B (paires)' => self::B,
        ];
    }

    /**
     * Retourne les choix avec option "toutes les semaines"
     * 
     * @return array<string, ?self>
     */
    public static function getFormChoicesAvecNull(): array
    {
        return [
            'Toutes les semaines' => null,
            'Semaine A (impaires)' => self::A,
            'Semaine B (paires)' => self::B,
        ];
    }
}
