<?php

namespace App\Service;

/**
 * Service de génération de mots de passe temporaires
 * Génère des mots de passe lisibles et mémorisables
 */
class PasswordGeneratorService
{
    /**
     * Génère un mot de passe temporaire lisible
     * Format: Mot + Chiffres + Caractère spécial (ex: Soleil42!)
     * 
     * @param int $length Longueur minimale souhaitée (défaut: 10)
     * @return string
     */
    public function generate(int $length = 10): string
    {
        // Liste de mots simples et mémorisables
        $words = [
            'Soleil', 'Lune', 'Etoile', 'Nuage', 'Ocean',
            'Montagne', 'Foret', 'Riviere', 'Jardin', 'Fleur',
            'Arbre', 'Vent', 'Pluie', 'Neige', 'Printemps',
            'Automne', 'Hiver', 'Matin', 'Soir', 'Aurore',
            'Horizon', 'Cascade', 'Prairie', 'Colline', 'Vallee',
            'Papillon', 'Oiseau', 'Dauphin', 'Tigre', 'Aigle',
            'Phoenix', 'Dragon', 'Licorne', 'Pegase', 'Sirene',
            'Crystal', 'Diamant', 'Rubis', 'Emeraude', 'Saphir',
            'Bronze', 'Argent', 'Platine', 'Cobalt', 'Titane',
            'Cosmos', 'Galaxie', 'Comete', 'Eclipse', 'Nebula'
        ];
        
        $specialChars = ['!', '@', '#', '$', '%', '&', '*', '?'];
        
        // Sélectionner un mot aléatoire
        $word = $words[array_rand($words)];
        
        // Ajouter 2-3 chiffres
        $numbers = str_pad((string) random_int(10, 999), 2, '0', STR_PAD_LEFT);
        
        // Ajouter un caractère spécial
        $special = $specialChars[array_rand($specialChars)];
        
        return $word . $numbers . $special;
    }
    
    /**
     * Génère un mot de passe aléatoire classique (pour API ou usage technique)
     * 
     * @param int $length Longueur du mot de passe
     * @return string
     */
    public function generateRandom(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
