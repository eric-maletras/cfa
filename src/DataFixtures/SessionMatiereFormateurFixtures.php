<?php

namespace App\DataFixtures;

use App\Entity\SessionMatiereFormateur;
use App\Entity\Session;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les assignations de formateurs aux matières de session
 * 
 * À exécuter avec --append après les fixtures de sessions et formateurs
 */
class SessionMatiereFormateurFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les sessions avec leurs matières et formateurs
        $sessionRepo = $manager->getRepository(Session::class);
        $sessions = $sessionRepo->findAll();

        foreach ($sessions as $session) {
            $formateurs = $session->getFormateurs()->toArray();
            $sessionMatieres = $session->getSessionMatieres()->toArray();

            // Ignorer les sessions sans formateurs ou sans matières
            if (empty($formateurs) || empty($sessionMatieres)) {
                continue;
            }

            // Pour chaque matière, assigner 1 à 2 formateurs
            foreach ($sessionMatieres as $index => $sessionMatiere) {
                // Ne traiter que les matières actives
                if (!$sessionMatiere->isActif()) {
                    continue;
                }

                // Nombre de formateurs à assigner (1 ou 2)
                $nbFormateurs = min(count($formateurs), ($index % 3 === 0) ? 2 : 1);

                // Sélectionner des formateurs de manière pseudo-aléatoire
                $selectedFormateurs = $this->selectFormateurs($formateurs, $index, $nbFormateurs);

                foreach ($selectedFormateurs as $fIndex => $formateur) {
                    $smf = new SessionMatiereFormateur();
                    $smf->setSessionMatiere($sessionMatiere);
                    $smf->setFormateur($formateur);

                    // Premier formateur = responsable
                    if ($fIndex === 0) {
                        $smf->setEstResponsable(true);
                    }

                    // Calculer les heures assignées (répartition du volume)
                    $volumeEffectif = $sessionMatiere->getVolumeHeuresEffectif();
                    if ($nbFormateurs === 1) {
                        $smf->setHeuresAssignees($volumeEffectif);
                    } else {
                        // Répartition : 60% responsable, 40% second
                        $heures = $fIndex === 0 
                            ? (int) round($volumeEffectif * 0.6)
                            : $volumeEffectif - (int) round($volumeEffectif * 0.6);
                        $smf->setHeuresAssignees($heures);
                    }

                    // Ajouter un commentaire pour certaines assignations
                    if ($index % 5 === 0 && $fIndex === 0) {
                        $smf->setCommentaire('Intervenant principal - cours magistraux');
                    } elseif ($index % 5 === 0 && $fIndex === 1) {
                        $smf->setCommentaire('TP et travaux pratiques');
                    }

                    $manager->persist($smf);
                }
            }
        }

        $manager->flush();
    }

    /**
     * Sélectionne des formateurs de manière déterministe mais variée
     */
    private function selectFormateurs(array $formateurs, int $seed, int $count): array
    {
        $selected = [];
        $nbFormateurs = count($formateurs);

        for ($i = 0; $i < $count && $i < $nbFormateurs; $i++) {
            $index = ($seed + $i) % $nbFormateurs;
            $selected[] = $formateurs[$index];
        }

        return $selected;
    }
}
