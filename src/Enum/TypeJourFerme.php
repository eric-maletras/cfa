<?php

namespace App\Enum;

/**
 * Types de jours fermés pour le calendrier CFA
 */
enum TypeJourFerme: string
{
    case FERIE = 'ferie';
    case FERMETURE = 'fermeture';
    case VACANCES = 'vacances';
    case PONT = 'pont';

    /**
     * Retourne le libellé humain du type
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::FERIE => 'Jour férié',
            self::FERMETURE => 'Fermeture',
            self::VACANCES => 'Vacances',
            self::PONT => 'Pont',
        };
    }

    /**
     * Retourne la classe CSS pour les badges
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::FERIE => 'danger',
            self::FERMETURE => 'warning',
            self::VACANCES => 'info',
            self::PONT => 'secondary',
        };
    }

    /**
     * Retourne la couleur pour l'affichage calendrier
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::FERIE => '#dc3545',      // Rouge
            self::FERMETURE => '#fd7e14',   // Orange
            self::VACANCES => '#0d6efd',    // Bleu
            self::PONT => '#6c757d',        // Gris
        };
    }

    /**
     * Retourne l'icône emoji associée
     */
    public function getIcone(): string
    {
        return match ($this) {
            self::FERIE => '🎌',
            self::FERMETURE => '🔒',
            self::VACANCES => '🏖️',
            self::PONT => '🌉',
        };
    }
}
