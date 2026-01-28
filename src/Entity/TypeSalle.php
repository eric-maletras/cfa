<?php

namespace App\Entity;

/**
 * Enumération des types de salles
 * 
 * Définit les différentes catégories de salles disponibles
 * dans le CFA pour la gestion des plannings.
 */
enum TypeSalle: string
{
    case SALLE_COURS = 'salle_cours';
    case LABO_INFO = 'labo_info';
    case LABO_OPTIQUE = 'labo_optique';
    case AMPHI = 'amphi';
    case VIRTUEL = 'virtuel';

    /**
     * Retourne le libellé lisible du type
     */
    public function getLibelle(): string
    {
        return match($this) {
            self::SALLE_COURS => 'Salle de cours',
            self::LABO_INFO => 'Laboratoire informatique',
            self::LABO_OPTIQUE => 'Laboratoire optique',
            self::AMPHI => 'Amphithéâtre',
            self::VIRTUEL => 'Virtuel (distanciel)',
        };
    }

    /**
     * Retourne la classe CSS du badge pour l'affichage
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::SALLE_COURS => 'badge-primary',
            self::LABO_INFO => 'badge-success',
            self::LABO_OPTIQUE => 'badge-warning',
            self::AMPHI => 'badge-info',
            self::VIRTUEL => 'badge-secondary',
        };
    }

    /**
     * Retourne l'icône associée au type
     */
    public function getIcone(): string
    {
        return match($this) {
            self::SALLE_COURS => 'fa-chalkboard',
            self::LABO_INFO => 'fa-desktop',
            self::LABO_OPTIQUE => 'fa-eye',
            self::AMPHI => 'fa-users',
            self::VIRTUEL => 'fa-video',
        };
    }

    /**
     * Indique si ce type de salle nécessite une capacité
     */
    public function requiresCapacite(): bool
    {
        return $this !== self::VIRTUEL;
    }

    /**
     * Retourne tous les types pour les formulaires
     * 
     * @return array<string, TypeSalle>
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLibelle()] = $case;
        }
        return $choices;
    }
}
