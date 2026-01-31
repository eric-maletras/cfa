<?php

/**
 * MODIFICATIONS À AJOUTER À L'ENTITÉ Presence
 * 
 * Fichier: src/Entity/Presence.php
 * 
 * Ces modifications ajoutent la relation avec MotifAbsence
 * pour permettre de sélectionner un motif prédéfini lors de la justification.
 */

// =============================================================================
// 1. AJOUTER CET IMPORT EN HAUT DU FICHIER (après les autres use)
// =============================================================================

use App\Entity\MotifAbsence;


// =============================================================================
// 2. AJOUTER CETTE PROPRIÉTÉ (après les autres propriétés)
// =============================================================================

    /**
     * Motif d'absence prédéfini (optionnel)
     * Utilisé lors de la justification d'une absence
     */
    #[ORM\ManyToOne(targetEntity: MotifAbsence::class, inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MotifAbsence $motifAbsence = null;


// =============================================================================
// 3. AJOUTER CES MÉTHODES (getter et setter)
// =============================================================================

    public function getMotifAbsence(): ?MotifAbsence
    {
        return $this->motifAbsence;
    }

    public function setMotifAbsence(?MotifAbsence $motifAbsence): static
    {
        $this->motifAbsence = $motifAbsence;
        return $this;
    }


// =============================================================================
// 4. MODIFIER LA MÉTHODE justifierAbsence() SI ELLE EXISTE
//    Ou ajouter cette version améliorée
// =============================================================================

    /**
     * Justifie une absence avec un motif prédéfini et/ou un commentaire
     * 
     * @param string|null $commentaire Commentaire libre optionnel
     * @param MotifAbsence|null $motif Motif prédéfini (optionnel)
     */
    public function justifierAbsence(?string $commentaire = null, ?MotifAbsence $motif = null): static
    {
        // Vérifier que c'est bien une absence
        if (!in_array($this->statut, [StatutPresence::ABSENT, StatutPresence::NON_SIGNE])) {
            throw new \LogicException('Seule une absence peut être justifiée.');
        }

        $this->statut = StatutPresence::ABSENT_JUSTIFIE;
        $this->motifAbsence = $motif;
        
        if ($commentaire) {
            $this->motifAbsence = $commentaire; // Ancien champ texte si vous voulez le conserver
        }
        
        return $this;
    }


// =============================================================================
// NOTE: Si l'entité Presence a déjà une propriété $motifAbsence de type string,
// vous pouvez la renommer en $commentaireAbsence et garder les deux :
// - $motifAbsence : relation vers MotifAbsence (motif prédéfini)
// - $commentaireAbsence : texte libre (commentaire)
// =============================================================================
