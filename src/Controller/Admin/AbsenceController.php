<?php

namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\MotifAbsence;
use App\Entity\Presence;
use App\Entity\Session;
use App\Entity\User;
use App\Enum\StatutPresence;
use App\Form\JustifierAbsenceType;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use App\Repository\MotifAbsenceRepository;
use App\Repository\PresenceRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration des absences
 * 
 * Gère la visualisation, le suivi et la justification des absences des apprentis.
 * Accessible uniquement aux administrateurs.
 */
#[Route('/admin/absences')]
#[IsGranted('ROLE_ADMIN')]
class AbsenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private PresenceRepository $presenceRepo,
        private InscriptionRepository $inscriptionRepo,
        private SessionRepository $sessionRepo,
        private FormationRepository $formationRepo,
        private MotifAbsenceRepository $motifRepo
    ) {}

    /**
     * Liste des apprentis avec leurs statistiques d'absences
     */
    #[Route('', name: 'admin_absence_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer les filtres
        $filtreFormation = $request->query->get('formation');
        $filtreSession = $request->query->get('session');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $recherche = $request->query->get('q');
        $heuresMin = $request->query->get('heures_min');

        // Convertir les dates
        $dateDebutObj = $dateDebut ? new \DateTime($dateDebut) : null;
        $dateFinObj = $dateFin ? new \DateTime($dateFin) : null;

        // Récupérer les apprentis selon les filtres
        $apprentis = $this->getApprentisWithFilters(
            $filtreFormation ? (int) $filtreFormation : null,
            $filtreSession ? (int) $filtreSession : null,
            $recherche
        );

        // Calculer les statistiques pour chaque apprenti
        $apprentisStats = [];
        foreach ($apprentis as $apprenti) {
            $stats = $this->presenceRepo->getStatistiquesApprenti(
                $apprenti,
                $dateDebutObj,
                $dateFinObj
            );
            
            // Calculer les heures d'absence
            $heuresAbsence = $this->calculerHeuresAbsence(
                $apprenti,
                $dateDebutObj,
                $dateFinObj
            );

            // Filtrer par heures minimum si spécifié
            if ($heuresMin !== null && $heuresMin !== '' && $heuresAbsence < (float) $heuresMin) {
                continue;
            }

            $apprentisStats[] = [
                'apprenti' => $apprenti,
                'stats' => $stats,
                'heuresAbsence' => $heuresAbsence,
                'inscriptions' => $this->inscriptionRepo->findActiveByUser($apprenti),
            ];
        }

        // Trier par nombre d'absences décroissant
        usort($apprentisStats, function ($a, $b) {
            $absA = ($a['stats']['absents'] ?? 0) + ($a['stats']['nonSignes'] ?? 0);
            $absB = ($b['stats']['absents'] ?? 0) + ($b['stats']['nonSignes'] ?? 0);
            return $absB <=> $absA;
        });

        // Récupérer les formations et sessions pour les filtres
        $formations = $this->formationRepo->findBy(['actif' => true], ['intitule' => 'ASC']);
        $sessions = $this->sessionRepo->findBy(['actif' => true], ['dateDebut' => 'DESC']);

        return $this->render('admin/absences/index.html.twig', [
            'apprentisStats' => $apprentisStats,
            'formations' => $formations,
            'sessions' => $sessions,
            'filtreFormation' => $filtreFormation,
            'filtreSession' => $filtreSession,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'recherche' => $recherche,
            'heuresMin' => $heuresMin,
        ]);
    }

    /**
     * Détail des absences d'un apprenti
     */
    #[Route('/{id}', name: 'admin_absence_show', methods: ['GET'])]
    public function show(User $apprenti, Request $request): Response
    {
        // Vérifier que c'est bien un apprenti
        if (!$apprenti->isApprenti()) {
            $this->addFlash('error', 'Cet utilisateur n\'est pas un apprenti.');
            return $this->redirectToRoute('admin_absence_index');
        }

        // Récupérer les filtres
        $filtreSession = $request->query->get('session');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $filtreStatut = $request->query->get('statut');

        // Convertir les dates (défaut : 3 derniers mois)
        $dateDebutObj = $dateDebut 
            ? new \DateTime($dateDebut) 
            : (new \DateTime())->modify('-3 months');
        $dateFinObj = $dateFin 
            ? new \DateTime($dateFin) 
            : new \DateTime();

        // Récupérer les présences de l'apprenti
        $presences = $this->presenceRepo->findByApprentiAndPeriode(
            $apprenti,
            $dateDebutObj,
            $dateFinObj
        );

        // Filtrer par session si demandé
        if ($filtreSession) {
            $presences = array_filter($presences, function ($presence) use ($filtreSession) {
                $session = $presence->getAppel()?->getSeance()?->getSession();
                return $session && $session->getId() == $filtreSession;
            });
        }

        // Filtrer par statut si demandé
        if ($filtreStatut) {
            $presences = array_filter($presences, function ($presence) use ($filtreStatut) {
                return $presence->getStatut()->value === $filtreStatut;
            });
        }

        // Regrouper par mois pour l'affichage
        $presencesParMois = $this->grouperPresencesParMois($presences);

        // Statistiques globales
        $statsGlobales = $this->presenceRepo->getStatistiquesApprenti($apprenti);
        
        // Statistiques sur la période filtrée
        $statsPeriode = $this->presenceRepo->getStatistiquesApprenti(
            $apprenti,
            $dateDebutObj,
            $dateFinObj
        );

        // Heures d'absence
        $heuresAbsencePeriode = $this->calculerHeuresAbsence(
            $apprenti,
            $dateDebutObj,
            $dateFinObj
        );

        // Inscriptions actives de l'apprenti
        $inscriptions = $this->inscriptionRepo->findActiveByUser($apprenti);

        // Sessions pour le filtre
        $sessionsApprenti = [];
        foreach ($inscriptions as $inscription) {
            $sessionsApprenti[] = $inscription->getSession();
        }

        // Motifs d'absence pour le formulaire de justification
        $motifsAbsence = $this->motifRepo->findActifs();

        return $this->render('admin/absences/show.html.twig', [
            'apprenti' => $apprenti,
            'presencesParMois' => $presencesParMois,
            'statsGlobales' => $statsGlobales,
            'statsPeriode' => $statsPeriode,
            'heuresAbsencePeriode' => $heuresAbsencePeriode,
            'inscriptions' => $inscriptions,
            'sessionsApprenti' => $sessionsApprenti,
            'filtreSession' => $filtreSession,
            'dateDebut' => $dateDebutObj->format('Y-m-d'),
            'dateFin' => $dateFinObj->format('Y-m-d'),
            'filtreStatut' => $filtreStatut,
            'statutsPresence' => StatutPresence::cases(),
            'motifsAbsence' => $motifsAbsence,
        ]);
    }

    /**
     * Justifier une absence individuelle
     */
    #[Route('/justifier/{id}', name: 'admin_absence_justifier', methods: ['POST'])]
    public function justifier(Request $request, Presence $presence): Response
    {
        $apprenti = $presence->getApprenti();

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('justifier_' . $presence->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Vérifier que l'absence peut être justifiée
        if (!$presence->peutEtreJustifiee()) {
            $this->addFlash('error', 'Cette présence ne peut pas être justifiée (statut actuel : ' . $presence->getStatut()->getLibelle() . ').');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Récupérer le motif
        $motifId = $request->request->get('motif_absence_id');
        $commentaire = trim($request->request->get('commentaire', ''));

        if (!$motifId) {
            $this->addFlash('error', 'Veuillez sélectionner un motif d\'absence.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        $motif = $this->motifRepo->find($motifId);
        if (!$motif || !$motif->isActif()) {
            $this->addFlash('error', 'Motif d\'absence invalide.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Justifier l'absence
        $presence->justifierAbsence($motif, $commentaire ?: null);
        $this->em->flush();

        $seance = $presence->getAppel()?->getSeance();
        $dateSeance = $seance ? $seance->getDate()->format('d/m/Y') : 'inconnue';

        $this->addFlash('success', sprintf(
            'Absence du %s justifiée avec le motif "%s".',
            $dateSeance,
            $motif->getLibelle()
        ));

        return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
    }

    /**
     * Justifier plusieurs absences en masse
     */
    #[Route('/{id}/justifier-masse', name: 'admin_absence_justifier_masse', methods: ['POST'])]
    public function justifierMasse(Request $request, User $apprenti): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('justifier_masse_' . $apprenti->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Récupérer les IDs des présences sélectionnées
        $presenceIds = $request->request->all('presences');
        if (empty($presenceIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une absence à justifier.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Récupérer le motif
        $motifId = $request->request->get('motif_absence_id');
        $commentaire = trim($request->request->get('commentaire', ''));

        if (!$motifId) {
            $this->addFlash('error', 'Veuillez sélectionner un motif d\'absence.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        $motif = $this->motifRepo->find($motifId);
        if (!$motif || !$motif->isActif()) {
            $this->addFlash('error', 'Motif d\'absence invalide.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Justifier chaque absence
        $nbJustifiees = 0;
        $nbIgnorees = 0;

        foreach ($presenceIds as $presenceId) {
            $presence = $this->presenceRepo->find($presenceId);
            
            if (!$presence) {
                $nbIgnorees++;
                continue;
            }

            // Vérifier que la présence appartient bien à cet apprenti
            if ($presence->getApprenti()->getId() !== $apprenti->getId()) {
                $nbIgnorees++;
                continue;
            }

            // Vérifier que l'absence peut être justifiée
            if (!$presence->peutEtreJustifiee()) {
                $nbIgnorees++;
                continue;
            }

            $presence->justifierAbsence($motif, $commentaire ?: null);
            $nbJustifiees++;
        }

        $this->em->flush();

        if ($nbJustifiees > 0) {
            $this->addFlash('success', sprintf(
                '%d absence(s) justifiée(s) avec le motif "%s".',
                $nbJustifiees,
                $motif->getLibelle()
            ));
        }

        if ($nbIgnorees > 0) {
            $this->addFlash('warning', sprintf(
                '%d absence(s) ignorée(s) (déjà justifiées ou statut incompatible).',
                $nbIgnorees
            ));
        }

        return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
    }

    /**
     * Annuler la justification d'une absence
     */
    #[Route('/annuler-justification/{id}', name: 'admin_absence_annuler_justification', methods: ['POST'])]
    public function annulerJustification(Request $request, Presence $presence): Response
    {
        $apprenti = $presence->getApprenti();

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('annuler_justif_' . $presence->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Vérifier que c'est bien une absence justifiée
        if ($presence->getStatut() !== StatutPresence::ABSENT_JUSTIFIE) {
            $this->addFlash('error', 'Cette présence n\'est pas une absence justifiée.');
            return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
        }

        // Annuler la justification
        $presence->setStatut(StatutPresence::ABSENT);
        $presence->setMotifAbsence(null);
        $presence->setCommentaireJustification(null);
        $this->em->flush();

        $seance = $presence->getAppel()?->getSeance();
        $dateSeance = $seance ? $seance->getDate()->format('d/m/Y') : 'inconnue';

        $this->addFlash('success', sprintf(
            'Justification de l\'absence du %s annulée.',
            $dateSeance
        ));

        return $this->redirectToRoute('admin_absence_show', ['id' => $apprenti->getId()]);
    }

    /**
     * Récupère les apprentis selon les filtres
     */
    private function getApprentisWithFilters(
        ?int $formationId,
        ?int $sessionId,
        ?string $recherche
    ): array {
        // Si filtre par session
        if ($sessionId) {
            return $this->inscriptionRepo->findApprentisActifsBySession($sessionId);
        }

        // Si filtre par formation
        if ($formationId) {
            return $this->inscriptionRepo->findApprentisActifsByFormation($formationId);
        }

        // Sinon, tous les apprentis actifs avec recherche optionnelle
        return $this->userRepo->findWithFilters(
            roleId: null,
            actif: true,
            recherche: $recherche,
            roleCode: 'ROLE_APPRENTI'
        );
    }

    /**
     * Calcule le nombre d'heures d'absence pour un apprenti
     */
    private function calculerHeuresAbsence(
        User $apprenti,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null
    ): float {
        $presences = $this->presenceRepo->findByApprentiAndPeriode(
            $apprenti,
            $dateDebut ?? (new \DateTime())->modify('-1 year'),
            $dateFin ?? new \DateTime()
        );

        $minutes = 0;
        foreach ($presences as $presence) {
            $statut = $presence->getStatut();
            
            // Compter uniquement les absences non justifiées
            if ($statut === StatutPresence::ABSENT) {
                $seance = $presence->getAppel()?->getSeance();
                if ($seance) {
                    $minutes += $seance->getDureeMinutes();
                }
            }
        }

        return round($minutes / 60, 1);
    }

    /**
     * Regroupe les présences par mois
     */
    private function grouperPresencesParMois(array $presences): array
    {
        $parMois = [];
        
        foreach ($presences as $presence) {
            $seance = $presence->getAppel()?->getSeance();
            if (!$seance || !$seance->getDate()) {
                continue;
            }
            
            $moisKey = $seance->getDate()->format('Y-m');
            $moisLabel = $this->formatMoisLabel($seance->getDate());
            
            if (!isset($parMois[$moisKey])) {
                $parMois[$moisKey] = [
                    'label' => $moisLabel,
                    'presences' => [],
                    'stats' => [
                        'total' => 0,
                        'presents' => 0,
                        'absents' => 0,
                        'retards' => 0,
                    ],
                ];
            }
            
            $parMois[$moisKey]['presences'][] = $presence;
            $parMois[$moisKey]['stats']['total']++;
            
            $statut = $presence->getStatut();
            if ($statut->compteCommePresent()) {
                $parMois[$moisKey]['stats']['presents']++;
                if ($statut === StatutPresence::RETARD) {
                    $parMois[$moisKey]['stats']['retards']++;
                }
            } elseif ($statut->compteCommeAbsent()) {
                $parMois[$moisKey]['stats']['absents']++;
            }
        }

        // Trier par mois décroissant
        krsort($parMois);

        return $parMois;
    }

    /**
     * Formate le libellé d'un mois
     */
    private function formatMoisLabel(\DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            'MMMM yyyy'
        );
        
        return ucfirst($formatter->format($date));
    }

    // ==========================================
    // STEP 10-3 : Rapport d'heures d'absence
    // ==========================================

    /**
     * Rapport détaillé des heures d'absence par apprenti
     */
    #[Route('/rapport', name: 'admin_absences_rapport', methods: ['GET'])]
    public function rapport(Request $request): Response
    {
        // Filtres
        $filtreFormation = $request->query->get('formation');
        $filtreSession = $request->query->get('session');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $seuilAlerte = $request->query->getInt('seuil', 20);

        // Dates par défaut : année scolaire en cours
        if (!$dateDebut) {
            $now = new \DateTime();
            $year = $now->format('n') >= 9 ? $now->format('Y') : $now->format('Y') - 1;
            $dateDebut = "$year-09-01";
        }
        if (!$dateFin) {
            $dateFin = (new \DateTime())->format('Y-m-d');
        }

        $dateDebutObj = new \DateTime($dateDebut);
        $dateFinObj = new \DateTime($dateFin);

        // Récupérer les apprentis selon les filtres
        $apprentis = $this->getApprentisWithFilters(
            $filtreFormation ? (int) $filtreFormation : null,
            $filtreSession ? (int) $filtreSession : null,
            null
        );

        // Calculer le rapport pour chaque apprenti
        $rapportData = [];
        $totaux = [
            'heuresAbsence' => 0,
            'heuresJustifiees' => 0,
            'heuresNonJustifiees' => 0,
            'nbApprentis' => count($apprentis),
            'nbEnAlerte' => 0,
        ];

        foreach ($apprentis as $apprenti) {
            $stats = $this->presenceRepo->getStatistiquesApprenti($apprenti, $dateDebutObj, $dateFinObj);
            $heuresDetail = $this->calculerHeuresAbsenceDetail($apprenti, $dateDebutObj, $dateFinObj);
            
            $rapportData[] = [
                'apprenti' => $apprenti,
                'stats' => $stats,
                'heuresAbsence' => $heuresDetail['total'],
                'heuresJustifiees' => $heuresDetail['justifiees'],
                'heuresNonJustifiees' => $heuresDetail['nonJustifiees'],
                'inscriptions' => $this->inscriptionRepo->findActiveByUser($apprenti),
                'enAlerte' => $heuresDetail['nonJustifiees'] >= $seuilAlerte,
            ];

            $totaux['heuresAbsence'] += $heuresDetail['total'];
            $totaux['heuresJustifiees'] += $heuresDetail['justifiees'];
            $totaux['heuresNonJustifiees'] += $heuresDetail['nonJustifiees'];
            if ($heuresDetail['nonJustifiees'] >= $seuilAlerte) {
                $totaux['nbEnAlerte']++;
            }
        }

        // Trier par heures non justifiées décroissantes
        usort($rapportData, fn($a, $b) => $b['heuresNonJustifiees'] <=> $a['heuresNonJustifiees']);

        // Données pour les filtres
        $formations = $this->formationRepo->findBy(['actif' => true], ['intitule' => 'ASC']);
        $sessions = $this->sessionRepo->findBy(['actif' => true], ['dateDebut' => 'DESC']);

        return $this->render('admin/absences/rapport.html.twig', [
            'rapportData' => $rapportData,
            'totaux' => $totaux,
            'formations' => $formations,
            'sessions' => $sessions,
            'filtreFormation' => $filtreFormation,
            'filtreSession' => $filtreSession,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'seuilAlerte' => $seuilAlerte,
        ]);
    }

    /**
     * Export CSV du rapport
     */
    #[Route('/rapport/export-csv', name: 'admin_absences_rapport_csv', methods: ['GET'])]
    public function rapportExportCsv(Request $request): Response
    {
        // Mêmes filtres que le rapport
        $filtreFormation = $request->query->get('formation');
        $filtreSession = $request->query->get('session');
        $dateDebut = $request->query->get('date_debut') ?: (new \DateTime())->modify('-1 year')->format('Y-m-d');
        $dateFin = $request->query->get('date_fin') ?: (new \DateTime())->format('Y-m-d');

        $dateDebutObj = new \DateTime($dateDebut);
        $dateFinObj = new \DateTime($dateFin);

        $apprentis = $this->getApprentisWithFilters(
            $filtreFormation ? (int) $filtreFormation : null,
            $filtreSession ? (int) $filtreSession : null,
            null
        );

        // Générer le CSV
        $csv = "Nom;Prénom;Email;Formation;Session;Heures totales;Heures justifiées;Heures non justifiées;Taux présence\n";

        foreach ($apprentis as $apprenti) {
            $stats = $this->presenceRepo->getStatistiquesApprenti($apprenti, $dateDebutObj, $dateFinObj);
            $heuresDetail = $this->calculerHeuresAbsenceDetail($apprenti, $dateDebutObj, $dateFinObj);
            $inscriptions = $this->inscriptionRepo->findActiveByUser($apprenti);
            
            $formation = '';
            $session = '';
            if (!empty($inscriptions)) {
                $inscription = $inscriptions[0];
                $formation = $inscription->getSession()->getFormation()->getIntituleCourt() 
                    ?? $inscription->getSession()->getFormation()->getIntitule();
                $session = $inscription->getSession()->getCode();
            }

            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%.1f;%.1f;%.1f;%d%%\n",
                $apprenti->getNom(),
                $apprenti->getPrenom(),
                $apprenti->getEmail(),
                $formation,
                $session,
                $heuresDetail['total'],
                $heuresDetail['justifiees'],
                $heuresDetail['nonJustifiees'],
                $stats['tauxPresence'] ?? 0
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf(
            'attachment; filename="rapport-absences-%s-%s.csv"',
            $dateDebutObj->format('Ymd'),
            $dateFinObj->format('Ymd')
        ));

        return $response;
    }

    /**
     * Export PDF du rapport (aperçu HTML)
     */
    #[Route('/rapport/export-pdf', name: 'admin_absences_rapport_pdf', methods: ['GET'])]
    public function rapportExportPdf(Request $request): Response
    {
        // Mêmes filtres que le rapport
        $filtreFormation = $request->query->get('formation');
        $filtreSession = $request->query->get('session');
        $dateDebut = $request->query->get('date_debut') ?: (new \DateTime())->modify('-1 year')->format('Y-m-d');
        $dateFin = $request->query->get('date_fin') ?: (new \DateTime())->format('Y-m-d');
        $seuilAlerte = $request->query->getInt('seuil', 20);

        $dateDebutObj = new \DateTime($dateDebut);
        $dateFinObj = new \DateTime($dateFin);

        $apprentis = $this->getApprentisWithFilters(
            $filtreFormation ? (int) $filtreFormation : null,
            $filtreSession ? (int) $filtreSession : null,
            null
        );

        // Calculer les données
        $rapportData = [];
        $totaux = [
            'heuresAbsence' => 0,
            'heuresJustifiees' => 0,
            'heuresNonJustifiees' => 0,
            'nbApprentis' => count($apprentis),
            'nbEnAlerte' => 0,
        ];

        foreach ($apprentis as $apprenti) {
            $stats = $this->presenceRepo->getStatistiquesApprenti($apprenti, $dateDebutObj, $dateFinObj);
            $heuresDetail = $this->calculerHeuresAbsenceDetail($apprenti, $dateDebutObj, $dateFinObj);
            
            $rapportData[] = [
                'apprenti' => $apprenti,
                'stats' => $stats,
                'heuresAbsence' => $heuresDetail['total'],
                'heuresJustifiees' => $heuresDetail['justifiees'],
                'heuresNonJustifiees' => $heuresDetail['nonJustifiees'],
                'inscriptions' => $this->inscriptionRepo->findActiveByUser($apprenti),
                'enAlerte' => $heuresDetail['nonJustifiees'] >= $seuilAlerte,
            ];

            $totaux['heuresAbsence'] += $heuresDetail['total'];
            $totaux['heuresJustifiees'] += $heuresDetail['justifiees'];
            $totaux['heuresNonJustifiees'] += $heuresDetail['nonJustifiees'];
            if ($heuresDetail['nonJustifiees'] >= $seuilAlerte) {
                $totaux['nbEnAlerte']++;
            }
        }

        usort($rapportData, fn($a, $b) => $b['heuresNonJustifiees'] <=> $a['heuresNonJustifiees']);

        return $this->render('admin/absences/rapport_pdf.html.twig', [
            'rapportData' => $rapportData,
            'totaux' => $totaux,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'seuilAlerte' => $seuilAlerte,
        ]);
    }

    /**
     * Calcule les heures d'absence avec détail justifié/non justifié
     */
    private function calculerHeuresAbsenceDetail(
        User $apprenti,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null
    ): array {
        $presences = $this->presenceRepo->findByApprentiAndPeriode(
            $apprenti,
            $dateDebut ?? (new \DateTime())->modify('-1 year'),
            $dateFin ?? new \DateTime()
        );

        $minutesJustifiees = 0;
        $minutesNonJustifiees = 0;

        foreach ($presences as $presence) {
            $statut = $presence->getStatut();
            $seance = $presence->getAppel()?->getSeance();
            
            if (!$seance) {
                continue;
            }

            $duree = $seance->getDureeMinutes();

            if ($statut === StatutPresence::ABSENT_JUSTIFIE) {
                $minutesJustifiees += $duree;
            } elseif ($statut === StatutPresence::ABSENT || $statut === StatutPresence::NON_SIGNE) {
                $minutesNonJustifiees += $duree;
            }
        }

        return [
            'justifiees' => round($minutesJustifiees / 60, 1),
            'nonJustifiees' => round($minutesNonJustifiees / 60, 1),
            'total' => round(($minutesJustifiees + $minutesNonJustifiees) / 60, 1),
        ];
    }
}
