<?php

namespace App\Service;

use App\Entity\Appel;
use App\Entity\Inscription;
use App\Entity\Presence;
use App\Entity\SeancePlanifiee;
use App\Entity\User;
use App\Enum\StatutPresence;
use App\Repository\AppelRepository;
use App\Repository\PresenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service de gestion des appels et présences
 * 
 * Ce service gère toute la logique métier liée aux appels :
 * - Création et initialisation des appels
 * - Génération des tokens de signature
 * - Envoi des emails de signature
 * - Traitement des signatures
 * - Réouverture pour retardataires (avec calcul automatique du retard)
 * - Clôture des appels
 * 
 * IMPORTANT: Le formateur ne peut PAS valider manuellement une présence.
 * Le retard est calculé automatiquement par blocs de 15 minutes.
 */
class AppelService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AppelRepository $appelRepo,
        private PresenceRepository $presenceRepo,
        private EmailService $emailService,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private int $expirationMinutes = 20 // Délai par défaut en MINUTES
    ) {}

    /**
     * Crée un nouvel appel pour une séance
     * 
     * @param SeancePlanifiee $seance La séance concernée
     * @param User $formateur Le formateur qui fait l'appel
     * @param array $apprentisPresents Liste des IDs des apprentis présents physiquement
     * @param int|null $expirationMinutes Durée de validité des liens (en MINUTES : 15, 20, 40)
     * @return Appel L'appel créé
     */
    public function creerAppel(
        SeancePlanifiee $seance,
        User $formateur,
        array $apprentisPresents = [],
        ?int $expirationMinutes = null
    ): Appel {
        // Vérifier s'il existe déjà un appel actif (non clôturé)
        $appelExistant = $this->appelRepo->findActiveBySeance($seance);
        if ($appelExistant) {
            throw new \LogicException('Un appel est déjà en cours pour cette séance. Clôturez-le avant d\'en créer un nouveau.');
        }

        // Calculer la date d'expiration EN MINUTES
        $expiration = $expirationMinutes ?? $this->expirationMinutes;
        
        // Valider les valeurs autorisées (15, 20, 40 minutes)
        $valeursAutorisees = [15, 20, 40];
        if (!in_array($expiration, $valeursAutorisees)) {
            $expiration = 20;
        }
        
        $dateExpiration = (new \DateTime())->modify("+{$expiration} minutes");
        
        // S'assurer que l'expiration ne dépasse pas la fin de la journée
        $finJournee = (new \DateTime())->setTime(23, 59, 59);
        if ($dateExpiration > $finJournee) {
            $dateExpiration = $finJournee;
        }

        // Créer l'appel
        $appel = new Appel();
        $appel->setSeance($seance)
              ->setFormateur($formateur)
              ->setDateExpiration($dateExpiration);

        $this->em->persist($appel);

        // Récupérer les apprentis de la session
        $session = $seance->getSession();
        if (!$session) {
            throw new \LogicException('La séance n\'est pas associée à une session.');
        }

        $inscriptions = $session->getInscriptionsValidees();
        
        // Créer les présences pour chaque apprenti inscrit
        foreach ($inscriptions as $inscription) {
            $apprenti = $inscription->getUser();
            $presence = new Presence();
            $presence->setAppel($appel)
                     ->setApprenti($apprenti);

            // Déterminer le statut initial
            if (in_array($apprenti->getId(), $apprentisPresents)) {
                // Apprenti présent physiquement → génère un token pour signature
                $presence->setStatut(StatutPresence::EN_ATTENTE)
                         ->genererToken();
            } else {
                // Apprenti absent
                $presence->marquerAbsent();
            }

            $appel->addPresence($presence);
            $this->em->persist($presence);
        }

        $this->em->flush();

        $this->logger->info('Appel créé', [
            'appel_id' => $appel->getId(),
            'seance_id' => $seance->getId(),
            'formateur' => $formateur->getEmail(),
            'nb_presences' => $appel->getPresences()->count(),
            'nb_en_attente' => $appel->getNbEnAttente(),
            'expiration_minutes' => $expiration,
        ]);

        return $appel;
    }

    /**
     * Envoie les emails de signature aux apprentis en attente
     */
    public function envoyerEmails(Appel $appel): array
    {
        if ($appel->isCloture()) {
            throw new \LogicException('Impossible d\'envoyer des emails pour un appel clôturé.');
        }

        if ($appel->isEmailsEnvoyes()) {
            throw new \LogicException('Les emails ont déjà été envoyés pour cet appel.');
        }

        $resultats = [
            'succes' => 0,
            'echecs' => 0,
            'details' => [],
        ];

        $seance = $appel->getSeance();
        $presencesEnAttente = $this->presenceRepo->findEnAttenteByAppel($appel);

        foreach ($presencesEnAttente as $presence) {
            $apprenti = $presence->getApprenti();
            
            // Générer le lien de signature
            $lienSignature = $this->urlGenerator->generate(
                'app_signature_signer',
                ['token' => $presence->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Préparer le contexte pour le template email
            $context = [
                'apprenti' => $apprenti,
                'seance' => $seance,
                'appel' => $appel,
                'lienSignature' => $lienSignature,
                'dateExpiration' => $appel->getDateExpiration(),
            ];

            // Envoyer l'email
            $result = $this->emailService->sendTemplatedEmail(
                $apprenti->getEmail(),
                sprintf(
                    '[CFA] Signature de présence - %s du %s',
                    $seance->getSessionMatiere()?->getMatiere()?->getLibelle() ?? 'Cours',
                    $seance->getDate()->format('d/m/Y')
                ),
                'email/signature_presence.html.twig',
                $context
            );

            if ($result->success) {
                $presence->setEmailEnvoye(true)
                         ->setDateEnvoiEmail(new \DateTime());
                $resultats['succes']++;
            } else {
                $resultats['echecs']++;
            }

            $resultats['details'][] = [
                'apprenti' => $apprenti->getNomComplet(),
                'email' => $apprenti->getEmail(),
                'succes' => $result->success,
                'message' => $result->message,
            ];

            // Petit délai entre les envois
            usleep(100000); // 100ms
        }

        // Marquer l'appel comme emails envoyés
        $appel->setEmailsEnvoyes(true)
              ->setDateEnvoiEmails(new \DateTime());

        $this->em->flush();

        $this->logger->info('Emails de signature envoyés', [
            'appel_id' => $appel->getId(),
            'succes' => $resultats['succes'],
            'echecs' => $resultats['echecs'],
        ]);

        return $resultats;
    }

    /**
     * Traite la signature d'un apprenti via son token
     * 
     * Si l'apprenti a un retard enregistré (réouverture), la signature valide le statut RETARD
     */
    public function traiterSignature(string $token, string $ip, string $userAgent): array
    {
        $presence = $this->presenceRepo->findByToken($token);

        if (!$presence) {
            return [
                'succes' => false,
                'message' => 'Lien de signature invalide ou expiré.',
                'code' => 'TOKEN_INVALIDE',
            ];
        }

        if (!$presence->peutEtreSignee()) {
            // Déterminer la raison
            if ($presence->getStatut() === StatutPresence::PRESENT) {
                return [
                    'succes' => false,
                    'message' => 'Vous avez déjà signé votre présence.',
                    'code' => 'DEJA_SIGNE',
                    'presence' => $presence,
                ];
            }

            if ($presence->getStatut() === StatutPresence::RETARD && $presence->getDateSignature()) {
                return [
                    'succes' => false,
                    'message' => 'Vous avez déjà signé votre présence (avec retard).',
                    'code' => 'DEJA_SIGNE',
                    'presence' => $presence,
                ];
            }

            if ($presence->getAppel()->isCloture()) {
                return [
                    'succes' => false,
                    'message' => 'L\'appel a été clôturé par le formateur.',
                    'code' => 'APPEL_CLOTURE',
                ];
            }

            if (!$presence->getAppel()->isLiensValides()) {
                return [
                    'succes' => false,
                    'message' => 'Le délai de signature a expiré.',
                    'code' => 'LIEN_EXPIRE',
                ];
            }

            return [
                'succes' => false,
                'message' => 'Signature impossible.',
                'code' => 'ERREUR_INCONNUE',
            ];
        }

        // Effectuer la signature
        // Si minutesRetard est défini, c'est un retardataire → statut RETARD
        if ($presence->getMinutesRetard() !== null && $presence->getMinutesRetard() > 0) {
            $presence->setStatut(StatutPresence::RETARD);
        } else {
            $presence->setStatut(StatutPresence::PRESENT);
        }
        
        $presence->setDateSignature(new \DateTime())
                 ->setIpSignature($ip)
                 ->setUserAgentSignature($userAgent);
        
        $this->em->flush();

        $this->logger->info('Présence signée', [
            'presence_id' => $presence->getId(),
            'apprenti' => $presence->getApprenti()->getEmail(),
            'ip' => $ip,
            'statut' => $presence->getStatut()->value,
            'retard' => $presence->getMinutesRetard(),
        ]);

        $message = 'Votre présence a été enregistrée avec succès.';
        if ($presence->getMinutesRetard()) {
            $message .= sprintf(' (Retard : %d minutes)', $presence->getMinutesRetard());
        }

        return [
            'succes' => true,
            'message' => $message,
            'code' => 'OK',
            'presence' => $presence,
        ];
    }

    /**
     * Rouvre un appel clôturé pour permettre aux retardataires de signer
     * 
     * - Conserve les signatures existantes
     * - Génère de nouveaux tokens pour les retardataires sélectionnés
     * - Calcule automatiquement le retard par blocs de 15mn
     *   ("un bloc démarré = un bloc consommé")
     * - Envoie les emails aux retardataires
     * 
     * @param Appel $appel L'appel à rouvrir
     * @param array $retardatairesIds IDs des présences à marquer comme retardataires
     * @param int $expirationMinutes Durée de validité des nouveaux liens (15, 20, 40)
     * @return array Statistiques de l'opération
     */
    public function rouvrirAppel(Appel $appel, array $retardatairesIds, int $expirationMinutes = 15): array
    {
        // Valider les valeurs autorisées
        $valeursAutorisees = [15, 20, 40];
        if (!in_array($expirationMinutes, $valeursAutorisees)) {
            $expirationMinutes = 15;
        }

        // Calculer le retard depuis le début de la séance (ou depuis la création de l'appel)
        $seance = $appel->getSeance();
        $heureDebut = $seance->getDate()->setTime(
            (int) $seance->getHeureDebut()->format('H'),
            (int) $seance->getHeureDebut()->format('i')
        );
        
        $maintenant = new \DateTime();
        $diffMinutes = ($maintenant->getTimestamp() - $heureDebut->getTimestamp()) / 60;
        
        // Calcul par blocs de 15mn : "un bloc démarré = un bloc consommé"
        $minutesRetard = (int) ceil($diffMinutes / 15) * 15;
        
        // Minimum 15 minutes, maximum raisonnable
        if ($minutesRetard < 15) {
            $minutesRetard = 15;
        }
        if ($minutesRetard > 240) { // Max 4h
            $minutesRetard = 240;
        }

        // Nouvelle date d'expiration
        $dateExpiration = (new \DateTime())->modify("+{$expirationMinutes} minutes");
        $finJournee = (new \DateTime())->setTime(23, 59, 59);
        if ($dateExpiration > $finJournee) {
            $dateExpiration = $finJournee;
        }

        // Rouvrir l'appel
        $appel->setCloture(false)
              ->setDateCloture(null)
              ->setDateExpiration($dateExpiration);

        $nbRetardataires = 0;
        $emailsEnvoyes = 0;

        // Traiter chaque retardataire
        foreach ($appel->getPresences() as $presence) {
            if (in_array($presence->getId(), $retardatairesIds)) {
                // Vérifier que ce n'est pas déjà un présent/signé
                $statut = $presence->getStatut();
                if ($statut === StatutPresence::PRESENT || 
                    ($statut === StatutPresence::RETARD && $presence->getDateSignature())) {
                    continue; // Déjà signé, on ne touche pas
                }

                // Configurer comme retardataire en attente de signature
                $presence->setStatut(StatutPresence::EN_ATTENTE)
                         ->setMinutesRetard($minutesRetard)
                         ->genererToken();

                $nbRetardataires++;

                // Envoyer l'email
                $apprenti = $presence->getApprenti();
                $lienSignature = $this->urlGenerator->generate(
                    'app_signature_signer',
                    ['token' => $presence->getToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $result = $this->emailService->sendTemplatedEmail(
                    $apprenti->getEmail(),
                    sprintf(
                        '[CFA] RETARD - Signature de présence - %s du %s',
                        $seance->getSessionMatiere()?->getMatiere()?->getLibelle() ?? 'Cours',
                        $seance->getDate()->format('d/m/Y')
                    ),
                    'email/signature_presence.html.twig',
                    [
                        'apprenti' => $apprenti,
                        'seance' => $seance,
                        'appel' => $appel,
                        'lienSignature' => $lienSignature,
                        'dateExpiration' => $dateExpiration,
                        'estRetardataire' => true,
                        'minutesRetard' => $minutesRetard,
                    ]
                );

                if ($result->success) {
                    $presence->setEmailEnvoye(true)
                             ->setDateEnvoiEmail(new \DateTime());
                    $emailsEnvoyes++;
                }

                usleep(100000); // 100ms entre les envois
            }
        }

        $this->em->flush();

        $this->logger->info('Appel rouvert pour retardataires', [
            'appel_id' => $appel->getId(),
            'nb_retardataires' => $nbRetardataires,
            'minutes_retard' => $minutesRetard,
            'emails_envoyes' => $emailsEnvoyes,
            'expiration_minutes' => $expirationMinutes,
        ]);

        return [
            'nbRetardataires' => $nbRetardataires,
            'minutesRetard' => $minutesRetard,
            'emailsEnvoyes' => $emailsEnvoyes,
            'dateExpiration' => $dateExpiration,
        ];
    }

    /**
     * Clôture un appel et marque les non-signés
     */
    public function cloturerAppel(Appel $appel): array
    {
        if ($appel->isCloture()) {
            throw new \LogicException('Cet appel est déjà clôturé.');
        }

        $nbNonSignes = 0;

        // Marquer tous les EN_ATTENTE comme NON_SIGNE
        foreach ($appel->getPresences() as $presence) {
            if ($presence->getStatut() === StatutPresence::EN_ATTENTE) {
                $presence->marquerNonSigne();
                $nbNonSignes++;
            }
        }

        $appel->cloturer();
        $this->em->flush();

        $this->logger->info('Appel clôturé', [
            'appel_id' => $appel->getId(),
            'nb_presents' => $appel->getNbPresents(),
            'nb_absents' => $appel->getNbAbsents(),
            'nb_non_signes' => $nbNonSignes,
        ]);

        return [
            'presents' => $appel->getNbPresents(),
            'absents' => $appel->getNbAbsents(),
            'nonSignes' => $nbNonSignes,
            'tauxPresence' => $appel->getTauxPresence(),
        ];
    }

    /**
     * Renvoie l'email de signature pour un apprenti spécifique
     */
    public function renvoyerEmail(Presence $presence): bool
    {
        if (!$presence->peutEtreSignee()) {
            return false;
        }

        $appel = $presence->getAppel();
        $seance = $appel->getSeance();
        $apprenti = $presence->getApprenti();

        $lienSignature = $this->urlGenerator->generate(
            'app_signature_signer',
            ['token' => $presence->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $sujet = sprintf(
            '[CFA] RAPPEL - Signature de présence - %s du %s',
            $seance->getSessionMatiere()?->getMatiere()?->getLibelle() ?? 'Cours',
            $seance->getDate()->format('d/m/Y')
        );

        // Ajouter info retard si applicable
        if ($presence->getMinutesRetard()) {
            $sujet = sprintf(
                '[CFA] RAPPEL RETARD - Signature de présence - %s du %s',
                $seance->getSessionMatiere()?->getMatiere()?->getLibelle() ?? 'Cours',
                $seance->getDate()->format('d/m/Y')
            );
        }

        $result = $this->emailService->sendTemplatedEmail(
            $apprenti->getEmail(),
            $sujet,
            'email/signature_presence.html.twig',
            [
                'apprenti' => $apprenti,
                'seance' => $seance,
                'appel' => $appel,
                'lienSignature' => $lienSignature,
                'dateExpiration' => $appel->getDateExpiration(),
                'estRappel' => true,
                'estRetardataire' => $presence->getMinutesRetard() !== null,
                'minutesRetard' => $presence->getMinutesRetard(),
            ]
        );

        if ($result->success) {
            $presence->setDateEnvoiEmail(new \DateTime());
            $this->em->flush();
        }

        return $result->success;
    }

    /**
     * Récupère les statistiques d'un appel
     */
    public function getStatistiquesAppel(Appel $appel): array
    {
        $counts = $this->presenceRepo->countByStatutForAppel($appel);

        $total = array_sum($counts);
        $presents = ($counts[StatutPresence::PRESENT->value] ?? 0) + ($counts[StatutPresence::RETARD->value] ?? 0);

        return [
            'total' => $total,
            'presents' => $presents,
            'absents' => $counts[StatutPresence::ABSENT->value] ?? 0,
            'absentsJustifies' => $counts[StatutPresence::ABSENT_JUSTIFIE->value] ?? 0,
            'retards' => $counts[StatutPresence::RETARD->value] ?? 0,
            'enAttente' => $counts[StatutPresence::EN_ATTENTE->value] ?? 0,
            'nonSignes' => $counts[StatutPresence::NON_SIGNE->value] ?? 0,
            'tauxPresence' => $total > 0 ? round(($presents / $total) * 100, 1) : 0,
            'parStatut' => $counts,
        ];
    }

    /**
     * Traitement automatique des appels expirés (à appeler via cron)
     */
    public function traiterAppelsExpires(): int
    {
        $appelsExpires = $this->appelRepo->findExpiredNonClotures();
        $count = 0;

        foreach ($appelsExpires as $appel) {
            $this->cloturerAppel($appel);
            $count++;
        }

        if ($count > 0) {
            $this->logger->info('Appels expirés traités automatiquement', [
                'count' => $count,
            ]);
        }

        return $count;
    }
}
