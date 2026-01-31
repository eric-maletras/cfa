<?php

namespace App\Enum;

/**
 * Statuts possibles pour une présence à une séance
 */
enum StatutPresence: string
{
    case EN_ATTENTE = 'en_attente';      // Lien envoyé, en attente de signature
    case PRESENT = 'present';             // Signature confirmée
    case ABSENT = 'absent';               // Non sélectionné par le formateur
    case ABSENT_JUSTIFIE = 'absent_justifie';  // Absent avec justificatif
    case RETARD = 'retard';               // Présent avec retard
    case NON_SIGNE = 'non_signe';         // Sélectionné mais n'a pas signé dans le délai

    /**
     * Retourne le libellé français du statut
     */
    public function getLibelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de signature',
            self::PRESENT => 'Présent',
            self::ABSENT => 'Absent',
            self::ABSENT_JUSTIFIE => 'Absent justifié',
            self::RETARD => 'Retard',
            self::NON_SIGNE => 'Non signé',
        };
    }

    /**
     * Retourne la classe CSS pour le badge
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'badge--warning',
            self::PRESENT => 'badge--success',
            self::ABSENT => 'badge--danger',
            self::ABSENT_JUSTIFIE => 'badge--info',
            self::RETARD => 'badge--warning',
            self::NON_SIGNE => 'badge--danger',
        };
    }

    /**
     * Retourne l'icône associée au statut
     */
    public function getIcone(): string
    {
        return match ($this) {
            self::EN_ATTENTE => '⏳',
            self::PRESENT => '✅',
            self::ABSENT => '❌',
            self::ABSENT_JUSTIFIE => '📋',
            self::RETARD => '⏰',
            self::NON_SIGNE => '⚠️',
        };
    }

    /**
     * Retourne la couleur pour les graphiques/stats
     */
    public function getCouleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE => '#ffc107',
            self::PRESENT => '#28a745',
            self::ABSENT => '#dc3545',
            self::ABSENT_JUSTIFIE => '#17a2b8',
            self::RETARD => '#fd7e14',
            self::NON_SIGNE => '#e83e8c',
        };
    }

    /**
     * Indique si le statut compte comme présent pour les statistiques
     */
    public function compteCommePresent(): bool
    {
        return in_array($this, [self::PRESENT, self::RETARD]);
    }

    /**
     * Indique si le statut compte comme absent pour les statistiques
     */
    public function compteCommeAbsent(): bool
    {
        return in_array($this, [self::ABSENT, self::ABSENT_JUSTIFIE, self::NON_SIGNE]);
    }
}
