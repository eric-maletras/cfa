<?php

namespace App\Enum;

/**
 * Enum des statuts possibles d'une séance planifiée
 * 
 * Workflow des statuts :
 * - PLANIFIEE : état initial après génération
 * - CONFIRMEE : séance validée, prête à avoir lieu
 * - ANNULEE : séance annulée (reste visible dans l'historique)
 * - REPORTEE : séance décalée (nécessite reprogrammation)
 */
enum StatutSeance: string
{
    case PLANIFIEE = 'planifiee';
    case CONFIRMEE = 'confirmee';
    case ANNULEE = 'annulee';
    case REPORTEE = 'reportee';

    /**
     * Retourne le libellé du statut
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::PLANIFIEE => 'Planifiée',
            self::CONFIRMEE => 'Confirmée',
            self::ANNULEE => 'Annulée',
            self::REPORTEE => 'Reportée',
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
        };
    }

    /**
     * Retourne la classe CSS pour le badge
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::PLANIFIEE => 'info',
            self::CONFIRMEE => 'success',
            self::ANNULEE => 'danger',
            self::REPORTEE => 'warning',
        };
    }

    /**
     * Retourne la couleur associée
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::PLANIFIEE => '#3498db',
            self::CONFIRMEE => '#27ae60',
            self::ANNULEE => '#e74c3c',
            self::REPORTEE => '#f39c12',
        };
    }

    /**
     * Vérifie si la séance est active (non annulée)
     */
    public function isActive(): bool
    {
        return $this !== self::ANNULEE;
    }

    /**
     * Vérifie si la séance peut être modifiée
     */
    public function isModifiable(): bool
    {
        return in_array($this, [self::PLANIFIEE, self::CONFIRMEE, self::REPORTEE]);
    }

    /**
     * Vérifie si la séance compte dans les heures réalisées
     */
    public function compteHeures(): bool
    {
        return $this === self::CONFIRMEE;
    }

    /**
     * Retourne les transitions possibles depuis ce statut
     * 
     * @return self[]
     */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::PLANIFIEE => [self::CONFIRMEE, self::ANNULEE, self::REPORTEE],
            self::CONFIRMEE => [self::ANNULEE, self::REPORTEE],
            self::ANNULEE => [], // Pas de transition depuis annulée
            self::REPORTEE => [self::PLANIFIEE, self::CONFIRMEE, self::ANNULEE],
        };
    }

    /**
     * Vérifie si une transition vers un autre statut est possible
     */
    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsPossibles());
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
