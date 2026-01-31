<?php

namespace App\DataFixtures;

use App\Entity\Appel;
use App\Entity\MotifAbsence;
use App\Entity\Presence;
use App\Entity\SeancePlanifiee;
use App\Entity\User;
use App\Enum\StatutPresence;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour générer des appels et présences de test
 * 
 * Crée des données réalistes pour tester le module absences :
 * - Appels clôturés sur les séances passées
 * - Mix de présents, absents, retards, absences justifiées
 * - Différents profils d'apprentis (assidus, moyens, absentéistes)
 * 
 * IMPORTANT: Ces fixtures créent des données de test pour valider
 * le rapport d'heures d'absence (étape 10-3)
 * 
 * Usage: php bin/console doctrine:fixtures:load --append --group=appel
 */
class AppelPresenceFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    // Profils de présence pour simuler différents comportements
    // Les pourcentages définissent la probabilité de chaque statut
    
    /** Apprenti très assidu - peu d'absences */
    private const PROFIL_ASSIDU = [
        'present' => 85,      // 85% présent
        'retard' => 10,       // 10% en retard
        'absent' => 3,        // 3% absent
        'absent_justifie' => 2, // 2% absence justifiée
    ];

    /** Apprenti moyen - quelques absences */
    private const PROFIL_MOYEN = [
        'present' => 70,
        'retard' => 10,
        'absent' => 12,
        'absent_justifie' => 8,
    ];

    /** Apprenti absentéiste - beaucoup d'absences */
    private const PROFIL_ABSENTEISTE = [
        'present' => 50,
        'retard' => 5,
        'absent' => 30,
        'absent_justifie' => 15,
    ];

    public function load(ObjectManager $manager): void
    {
        // Récupérer les formateurs
        $allUsers = $manager->getRepository(User::class)->findAll();
        $formateurs = array_filter($allUsers, fn($u) => in_array('ROLE_FORMATEUR', $u->getRoles()));
        $formateurs = array_values($formateurs);
        
        if (empty($formateurs)) {
            echo "Aucun formateur trouvé, fixtures ignorées.\n";
            return;
        }
        
        // Récupérer les séances passées (derniers 3 mois)
        $dateLimite = (new \DateTime())->modify('-3 months');
        $seances = $manager->getRepository(SeancePlanifiee::class)
            ->createQueryBuilder('s')
            ->where('s.date >= :dateLimite')
            ->andWhere('s.date < :aujourdhui')
            ->setParameter('dateLimite', $dateLimite)
            ->setParameter('aujourdhui', new \DateTime('today'))
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($seances)) {
            echo "Aucune séance passée trouvée, fixtures ignorées.\n";
            return;
        }

        echo sprintf("Génération d'appels pour %d séances...\n", count($seances));

        // Récupérer les motifs d'absence (si disponibles)
        $motifs = $manager->getRepository(MotifAbsence::class)->findBy(['actif' => true]);
        
        // Attribuer un profil aléatoire à chaque apprenti (une seule fois)
        $profilsApprentis = [];
        $compteurs = ['appels' => 0, 'presences' => 0];

        foreach ($seances as $seance) {
            $session = $seance->getSession();
            if (!$session) {
                continue;
            }

            // Récupérer les inscriptions validées
            $inscriptions = $session->getInscriptionsValidees();
            if ($inscriptions->isEmpty()) {
                continue;
            }

            // Vérifier s'il n'y a pas déjà un appel pour cette séance
            $appelExistant = $manager->getRepository(Appel::class)->findOneBy(['seance' => $seance]);
            if ($appelExistant) {
                continue;
            }

            // Choisir un formateur aléatoire parmi ceux assignés à la séance ou à la session
            $formateursSeance = $seance->getFormateurs()->toArray();
            if (empty($formateursSeance)) {
                $formateursSeance = $session->getFormateurs()->toArray();
            }
            if (empty($formateursSeance)) {
                $formateursSeance = $formateurs;
            }
            $formateur = $formateursSeance[array_rand($formateursSeance)];

            // Créer l'appel (clôturé)
            $appel = new Appel();
            $appel->setSeance($seance)
                  ->setFormateur($formateur)
                  ->setDateExpiration((clone $seance->getDate())->setTime(
                      (int) $seance->getHeureFin()->format('H'),
                      (int) $seance->getHeureFin()->format('i')
                  ))
                  ->setEmailsEnvoyes(true)
                  ->setDateEnvoiEmails($seance->getDate());
            
            // Clôturer l'appel
            $appel->cloturer();

            $manager->persist($appel);
            $compteurs['appels']++;

            // Créer les présences pour chaque apprenti inscrit
            foreach ($inscriptions as $inscription) {
                $apprenti = $inscription->getUser();
                $apprentiId = $apprenti->getId();

                // Attribuer un profil si pas encore fait (permanent pour l'apprenti)
                if (!isset($profilsApprentis[$apprentiId])) {
                    $rand = mt_rand(1, 100);
                    if ($rand <= 50) {
                        // 50% d'assidus
                        $profilsApprentis[$apprentiId] = self::PROFIL_ASSIDU;
                    } elseif ($rand <= 80) {
                        // 30% de moyens
                        $profilsApprentis[$apprentiId] = self::PROFIL_MOYEN;
                    } else {
                        // 20% d'absentéistes
                        $profilsApprentis[$apprentiId] = self::PROFIL_ABSENTEISTE;
                    }
                }

                $profil = $profilsApprentis[$apprentiId];
                $statut = $this->determinerStatut($profil);

                $presence = new Presence();
                $presence->setAppel($appel)
                         ->setApprenti($apprenti)
                         ->setStatut($statut);

                // Configurer selon le statut
                switch ($statut) {
                    case StatutPresence::PRESENT:
                        $presence->setDateSignature($this->genererDateSignature($seance))
                                 ->setIpSignature($this->genererIp())
                                 ->setUserAgentSignature('Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)');
                        break;

                    case StatutPresence::RETARD:
                        $minutesRetard = $this->genererMinutesRetard();
                        $presence->setMinutesRetard($minutesRetard)
                                 ->setDateSignature($this->genererDateSignatureRetard($seance, $minutesRetard))
                                 ->setIpSignature($this->genererIp())
                                 ->setUserAgentSignature('Mozilla/5.0 (Android 12; Mobile)');
                        break;

                    case StatutPresence::ABSENT_JUSTIFIE:
                        if (!empty($motifs)) {
                            $motif = $motifs[array_rand($motifs)];
                            $presence->setMotifAbsence($motif);
                            // 30% ont un commentaire supplémentaire
                            if (mt_rand(1, 100) <= 30) {
                                $presence->setCommentaireJustification('Justificatif fourni');
                            }
                        }
                        break;

                    case StatutPresence::ABSENT:
                        // Rien de spécial pour les absences non justifiées
                        break;
                }

                // Email envoyé sauf pour les absents
                $presence->setEmailEnvoye($statut !== StatutPresence::ABSENT);
                if ($presence->isEmailEnvoye()) {
                    $presence->setDateEnvoiEmail($seance->getDate());
                }

                $appel->addPresence($presence);
                $manager->persist($presence);
                $compteurs['presences']++;
            }

            // Flush par lots de 50 appels pour éviter les problèmes mémoire
            if ($compteurs['appels'] % 50 === 0) {
                $manager->flush();
                $manager->clear(Presence::class);
            }
        }

        $manager->flush();
        
        echo sprintf("Fixtures créées : %d appels, %d présences\n", 
            $compteurs['appels'], 
            $compteurs['presences']
        );
    }

    /**
     * Détermine le statut selon les probabilités du profil
     */
    private function determinerStatut(array $profil): StatutPresence
    {
        $rand = mt_rand(1, 100);
        $cumul = 0;

        $cumul += $profil['present'];
        if ($rand <= $cumul) {
            return StatutPresence::PRESENT;
        }

        $cumul += $profil['retard'];
        if ($rand <= $cumul) {
            return StatutPresence::RETARD;
        }

        $cumul += $profil['absent'];
        if ($rand <= $cumul) {
            return StatutPresence::ABSENT;
        }

        return StatutPresence::ABSENT_JUSTIFIE;
    }

    /**
     * Génère une date de signature réaliste (pendant le cours, dans les 15 premières minutes)
     */
    private function genererDateSignature(SeancePlanifiee $seance): \DateTime
    {
        $date = clone $seance->getDate();
        $heureDebut = (int) $seance->getHeureDebut()->format('H') * 60 + (int) $seance->getHeureDebut()->format('i');
        
        // Signature entre début et début + 15 minutes
        $minutesApresDebut = mt_rand(1, 15);
        $totalMinutes = $heureDebut + $minutesApresDebut;
        
        $date->setTime((int) ($totalMinutes / 60), $totalMinutes % 60, mt_rand(0, 59));
        
        return $date;
    }

    /**
     * Génère une date de signature pour un retardataire
     */
    private function genererDateSignatureRetard(SeancePlanifiee $seance, int $minutesRetard): \DateTime
    {
        $date = clone $seance->getDate();
        $heureDebut = (int) $seance->getHeureDebut()->format('H') * 60 + (int) $seance->getHeureDebut()->format('i');
        
        // Signature après le retard enregistré + quelques minutes
        $minutesApresDebut = $minutesRetard + mt_rand(1, 5);
        $totalMinutes = $heureDebut + $minutesApresDebut;
        
        $date->setTime((int) ($totalMinutes / 60), $totalMinutes % 60, mt_rand(0, 59));
        
        return $date;
    }

    /**
     * Génère des minutes de retard par blocs de 15 (standard CFA)
     */
    private function genererMinutesRetard(): int
    {
        // Distribution réaliste : plus de petits retards que de gros
        $blocs = [15, 15, 15, 15, 30, 30, 45, 60];
        return $blocs[array_rand($blocs)];
    }

    /**
     * Génère une IP aléatoire réaliste (réseau privé)
     */
    private function genererIp(): string
    {
        $prefixes = ['192.168.1.', '192.168.0.', '10.0.0.', '172.16.0.'];
        return $prefixes[array_rand($prefixes)] . mt_rand(10, 254);
    }

    public static function getGroups(): array
    {
        return ['appel', 'presence', 'test'];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PlanningFixtures::class,
            MotifAbsenceFixtures::class,
        ];
    }
}
