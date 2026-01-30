<?php

namespace App\Enum;

/**
 * Statuts possibles d'une séance planifiée
 */
enum StatutSeance: string
{
    case PLANIFIEE = 'planifiee';
    case CONFIRMEE = 'confirmee';
    case ANNULEE = 'annulee';
    case REPORTEE = 'reportee';
    case TERMINEE = 'terminee';

    /**
     * Retourne le libellé en français
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::PLANIFIEE => 'Planifiée',
            self::CONFIRMEE => 'Confirmée',
            self::ANNULEE => 'Annulée',
            self::REPORTEE => 'Reportée',
            self::TERMINEE => 'Terminée',
        };
    }

    /**
     * Retourne l'icône associée
     */
    public function getIcone(): string
    {
        return match ($this) {
            self::PLANIFIEE => '📋',
            self::CONFIRMEE => '✅',
            self::ANNULEE => '❌',
            self::REPORTEE => '🔄',
            self::TERMINEE => '✔️',
        };
    }

    /**
     * Retourne la classe CSS pour le badge
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::PLANIFIEE => 'badge--info',
            self::CONFIRMEE => 'badge--success',
            self::ANNULEE => 'badge--danger',
            self::REPORTEE => 'badge--warning',
            self::TERMINEE => 'badge--secondary',
        };
    }

    /**
     * Retourne la couleur associée
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::PLANIFIEE => '#17a2b8',
            self::CONFIRMEE => '#28a745',
            self::ANNULEE => '#dc3545',
            self::REPORTEE => '#ffc107',
            self::TERMINEE => '#6c757d',
        };
    }

    /**
     * Indique si la séance peut être modifiée
     */
    public function estModifiable(): bool
    {
        return match ($this) {
            self::PLANIFIEE, self::CONFIRMEE, self::REPORTEE => true,
            self::ANNULEE, self::TERMINEE => false,
        };
    }

    /**
     * Retourne les transitions possibles depuis ce statut
     * 
     * @return self[]
     */
    public function getTransitionsPossibles(): array
    {
        return match ($this) {
            self::PLANIFIEE => [self::CONFIRMEE, self::ANNULEE, self::REPORTEE],
            self::CONFIRMEE => [self::TERMINEE, self::ANNULEE, self::REPORTEE],
            self::ANNULEE => [self::PLANIFIEE], // Possibilité de réactiver
            self::REPORTEE => [self::PLANIFIEE, self::CONFIRMEE, self::ANNULEE],
            self::TERMINEE => [], // Pas de transition depuis terminée
        };
    }
}
