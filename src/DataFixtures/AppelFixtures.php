<?php

namespace App\DataFixtures;

use App\Entity\Appel;
use App\Entity\Presence;
use App\Entity\SeancePlanifiee;
use App\Entity\User;
use App\Enum\StatutPresence;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour le module d'appel
 * 
 * Crée des exemples d'appels avec différents états pour les tests et démos.
 * 
 * Dépendances :
 * - UserFixtures (formateurs et apprentis)
 * - SeancePlanifieeFixtures (séances)
 * 
 * Usage :
 *   php bin/console doctrine:fixtures:load --append --group=appel
 */
class AppelFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public static function getGroups(): array
    {
        return ['appel', 'demo'];
    }

    public function getDependencies(): array
    {
        return [
            // Dépendances réelles
            UserFixtures::class,
            PlanningFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Récupérer les entités nécessaires
        $formateurRepo = $manager->getRepository(User::class);
        $seanceRepo = $manager->getRepository(SeancePlanifiee::class);

        // Trouver un formateur
        $formateurs = $formateurRepo->createQueryBuilder('u')
            ->join('u.rolesEntities', 'r')
            ->where('r.code = :role')
            ->setParameter('role', 'ROLE_FORMATEUR')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($formateurs)) {
            echo "Aucun formateur trouvé. Fixtures d'appel ignorées.\n";
            return;
        }

        $formateur = $formateurs[0];

        // Trouver des séances récentes ou à venir
        $seances = $seanceRepo->createQueryBuilder('s')
            ->where('s.date >= :dateMin')
            ->setParameter('dateMin', (new \DateTime())->modify('-7 days'))
            ->orderBy('s.date', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        if (empty($seances)) {
            echo "Aucune séance récente trouvée. Fixtures d'appel ignorées.\n";
            return;
        }

        echo sprintf("Création des fixtures d'appel pour %d séance(s)...\n", count($seances));

        foreach ($seances as $index => $seance) {
            $session = $seance->getSession();
            if (!$session) {
                continue;
            }

            // Récupérer les apprentis de la session
            $inscriptions = $session->getInscriptionsValidees();
            if ($inscriptions->isEmpty()) {
                continue;
            }

            // Créer un appel
            $appel = new Appel();
            $appel->setSeance($seance)
                  ->setFormateur($formateur)
                  ->setDateAppel(new \DateTime('-' . ($index * 2) . ' hours'));

            // Varier les états d'appel selon l'index
            switch ($index) {
                case 0:
                    // Appel en cours (non clôturé, emails envoyés)
                    $appel->setDateExpiration((new \DateTime())->modify('+2 hours'))
                          ->setEmailsEnvoyes(true)
                          ->setDateEnvoiEmails(new \DateTime('-1 hour'));
                    break;

                case 1:
                    // Appel clôturé
                    $appel->setDateExpiration((new \DateTime())->modify('-1 hour'))
                          ->setEmailsEnvoyes(true)
                          ->setDateEnvoiEmails(new \DateTime('-3 hours'))
                          ->setCloture(true)
                          ->setDateCloture(new \DateTime('-30 minutes'));
                    break;

                case 2:
                    // Appel expiré mais pas encore clôturé (pour test cron)
                    $appel->setDateExpiration((new \DateTime())->modify('-30 minutes'))
                          ->setEmailsEnvoyes(true)
                          ->setDateEnvoiEmails(new \DateTime('-2 hours'));
                    break;

                default:
                    $appel->setDateExpiration((new \DateTime())->modify('+4 hours'));
            }

            $manager->persist($appel);

            // Créer les présences pour chaque apprenti
            $apprentiIndex = 0;
            foreach ($inscriptions as $inscription) {
                $apprenti = $inscription->getUser();
                
                $presence = new Presence();
                $presence->setAppel($appel)
                         ->setApprenti($apprenti)
                         ->genererToken();

                // Varier les statuts
                switch ($apprentiIndex % 5) {
                    case 0:
                        // Présent (a signé)
                        $presence->setStatut(StatutPresence::PRESENT)
                                 ->setDateSignature(new \DateTime('-30 minutes'))
                                 ->setIpSignature('192.168.1.' . rand(10, 250))
                                 ->setUserAgentSignature('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
                                 ->setEmailEnvoye(true)
                                 ->setDateEnvoiEmail(new \DateTime('-2 hours'));
                        break;

                    case 1:
                        // En attente de signature
                        $presence->setStatut(StatutPresence::EN_ATTENTE)
                                 ->setEmailEnvoye(true)
                                 ->setDateEnvoiEmail(new \DateTime('-1 hour'));
                        break;

                    case 2:
                        // Absent
                        $presence->setStatut(StatutPresence::ABSENT);
                        // Pas de token pour les absents
                        break;

                    case 3:
                        // Retard
                        $presence->setStatut(StatutPresence::RETARD)
                                 ->setMinutesRetard(15)
                                 ->setDateSignature(new \DateTime('-45 minutes'))
                                 ->setIpSignature('10.0.0.' . rand(10, 250))
                                 ->setUserAgentSignature('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0')
                                 ->setEmailEnvoye(true)
                                 ->setDateEnvoiEmail(new \DateTime('-2 hours'));
                        break;

                    case 4:
                        // Absent justifié
                        $presence->setStatut(StatutPresence::ABSENT_JUSTIFIE)
                                 ->setMotifAbsence('Certificat médical fourni');
                        break;
                }

                // Si l'appel est clôturé et la présence est en attente → non signé
                if ($appel->isCloture() && $presence->getStatut() === StatutPresence::EN_ATTENTE) {
                    $presence->setStatut(StatutPresence::NON_SIGNE);
                }

                $appel->addPresence($presence);
                $manager->persist($presence);
                
                $apprentiIndex++;
            }

            echo sprintf(
                "  - Appel #%d pour séance du %s : %d présence(s)\n",
                $index + 1,
                $seance->getDate()->format('d/m/Y'),
                $appel->getPresences()->count()
            );
        }

        $manager->flush();

        echo "Fixtures d'appel créées avec succès !\n";
    }
}
