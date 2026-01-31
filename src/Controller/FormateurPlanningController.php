<?php

namespace App\Controller;

use App\Entity\SeancePlanifiee;
use App\Entity\User;
use App\Enum\StatutSeance;
use App\Repository\SeancePlanifieeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de l'emploi du temps pour les formateurs
 * Affiche le planning personnel du formateur connecté
 */
#[Route('/module/formateur_planning')]
#[IsGranted('ROLE_FORMATEUR')]
class FormateurPlanningController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SeancePlanifieeRepository $seanceRepo
    ) {}

    /**
     * Vue calendrier mensuel - Page d'accueil du planning
     */
    #[Route('', name: 'app_formateur_planning_index', methods: ['GET'])]
    #[Route('/calendrier', name: 'app_formateur_planning_calendrier', methods: ['GET'])]
    public function calendrier(Request $request): Response
    {
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        // Récupérer le mois demandé (par défaut : mois courant)
        $annee = (int) $request->query->get('annee', date('Y'));
        $mois = (int) $request->query->get('mois', date('m'));
        
        // Valider les valeurs
        if ($mois < 1 || $mois > 12) {
            $mois = (int) date('m');
        }
        if ($annee < 2020 || $annee > 2100) {
            $annee = (int) date('Y');
        }
        
        // Calculer les bornes du mois
        $premierJour = new \DateTime(sprintf('%d-%02d-01', $annee, $mois));
        $dernierJour = (clone $premierJour)->modify('last day of this month');
        
        // Calculer mois précédent et suivant pour la navigation
        $moisPrecedent = (clone $premierJour)->modify('-1 month');
        $moisSuivant = (clone $premierJour)->modify('+1 month');
        
        // Récupérer les séances du formateur pour ce mois
        $seances = $this->seanceRepo->findByFormateurAndPeriode(
            $formateur,
            $premierJour,
            $dernierJour
        );
        
        // Regrouper les séances par jour pour l'affichage calendrier
        $seancesParJour = [];
        foreach ($seances as $seance) {
            $dateStr = $seance->getDate()->format('Y-m-d');
            if (!isset($seancesParJour[$dateStr])) {
                $seancesParJour[$dateStr] = [];
            }
            $seancesParJour[$dateStr][] = $seance;
        }
        
        // Construire le calendrier (grille de semaines)
        $calendrier = $this->construireCalendrier($annee, $mois, $seancesParJour);
        
        // Statistiques du mois
        $stats = [
            'total' => count($seances),
            'planifiees' => 0,
            'confirmees' => 0,
            'terminees' => 0,
            'annulees' => 0,
            'heures' => 0,
        ];
        
        foreach ($seances as $seance) {
            match ($seance->getStatut()) {
                StatutSeance::PLANIFIEE => $stats['planifiees']++,
                StatutSeance::CONFIRMEE => $stats['confirmees']++,
                StatutSeance::TERMINEE => $stats['terminees']++,
                StatutSeance::ANNULEE => $stats['annulees']++,
                default => null,
            };
            
            if ($seance->getStatut() !== StatutSeance::ANNULEE) {
                $stats['heures'] += $seance->getDureeMinutes() / 60;
            }
        }
        $stats['heures'] = round($stats['heures'], 1);
        
        // Nom du mois en français
        $nomMois = $this->getNomMoisFr($mois);
        
        return $this->render('formateur/planning/calendrier.html.twig', [
            'annee' => $annee,
            'mois' => $mois,
            'nomMois' => $nomMois,
            'calendrier' => $calendrier,
            'seancesParJour' => $seancesParJour,
            'moisPrecedent' => $moisPrecedent,
            'moisSuivant' => $moisSuivant,
            'stats' => $stats,
            'formateur' => $formateur,
            'aujourdhui' => new \DateTime('today'),
        ]);
    }

    /**
     * Vue détaillée d'un jour
     */
    #[Route('/jour/{date}', name: 'app_formateur_planning_jour', methods: ['GET'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
    public function jour(string $date): Response
    {
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Date invalide.');
            return $this->redirectToRoute('app_formateur_planning_index');
        }
        
        // Récupérer les séances du formateur pour ce jour
        $seances = $this->seanceRepo->findByFormateurAndDate($formateur, $dateObj);
        
        // Calculer jours précédent et suivant
        $jourPrecedent = (clone $dateObj)->modify('-1 day');
        $jourSuivant = (clone $dateObj)->modify('+1 day');
        
        // Nom du jour en français
        $nomJour = $this->getNomJourFr($dateObj->format('N'));
        
        // Statistiques de la journée
        $stats = [
            'total' => count($seances),
            'heures' => 0,
        ];
        
        foreach ($seances as $seance) {
            if ($seance->getStatut() !== StatutSeance::ANNULEE) {
                $stats['heures'] += $seance->getDureeMinutes() / 60;
            }
        }
        $stats['heures'] = round($stats['heures'], 1);
        
        return $this->render('formateur/planning/jour.html.twig', [
            'date' => $dateObj,
            'dateStr' => $date,
            'nomJour' => $nomJour,
            'seances' => $seances,
            'jourPrecedent' => $jourPrecedent,
            'jourSuivant' => $jourSuivant,
            'stats' => $stats,
            'formateur' => $formateur,
            'aujourdhui' => new \DateTime('today'),
        ]);
    }

    /**
     * Détail d'une séance
     */
    #[Route('/seance/{id}', name: 'app_formateur_planning_seance', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function seance(SeancePlanifiee $seance): Response
    {
        $this->checkAccess($seance);
        
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        // Récupérer la liste des apprentis de la session
        $apprentis = [];
        $session = $seance->getSession();
        if ($session) {
            $inscriptions = $session->getInscriptionsValidees();
            foreach ($inscriptions as $inscription) {
                $apprentis[] = $inscription->getUser();
            }
            // Trier par nom
            usort($apprentis, fn(User $a, User $b) => strcmp($a->getNom(), $b->getNom()));
        }
        
        // Nom du jour en français
        $nomJour = $this->getNomJourFr($seance->getDate()->format('N'));
        
        return $this->render('formateur/planning/seance.html.twig', [
            'seance' => $seance,
            'nomJour' => $nomJour,
            'apprentis' => $apprentis,
            'formateur' => $formateur,
            'transitionsPossibles' => $seance->getStatut()->getTransitionsPossibles(),
        ]);
    }

    /**
     * Changer le statut d'une séance
     */
    #[Route('/seance/{id}/statut', name: 'app_formateur_planning_seance_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changerStatut(Request $request, SeancePlanifiee $seance): Response
    {
        $this->checkAccess($seance);
        
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('change_statut_' . $seance->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_formateur_planning_seance', ['id' => $seance->getId()]);
        }
        
        $nouveauStatutValue = $request->request->get('statut');
        if (!$nouveauStatutValue) {
            $this->addFlash('error', 'Statut non spécifié.');
            return $this->redirectToRoute('app_formateur_planning_seance', ['id' => $seance->getId()]);
        }
        
        // Convertir en enum
        try {
            $nouveauStatut = StatutSeance::from($nouveauStatutValue);
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_formateur_planning_seance', ['id' => $seance->getId()]);
        }
        
        // Vérifier que la transition est autorisée
        $transitionsPossibles = $seance->getStatut()->getTransitionsPossibles();
        if (!in_array($nouveauStatut, $transitionsPossibles)) {
            $this->addFlash('error', sprintf(
                'La transition de "%s" vers "%s" n\'est pas autorisée.',
                $seance->getStatut()->getLibelle(),
                $nouveauStatut->getLibelle()
            ));
            return $this->redirectToRoute('app_formateur_planning_seance', ['id' => $seance->getId()]);
        }
        
        // Appliquer le changement
        $ancienStatut = $seance->getStatut();
        $seance->setStatut($nouveauStatut);
        $seance->setModifieeDepuisCreneau(true);
        
        $this->em->flush();
        
        $this->addFlash('success', sprintf(
            'Statut modifié : "%s" → "%s"',
            $ancienStatut->getLibelle(),
            $nouveauStatut->getLibelle()
        ));
        
        return $this->redirectToRoute('app_formateur_planning_seance', ['id' => $seance->getId()]);
    }

    /**
     * Vue semaine (optionnel - accès rapide)
     */
    #[Route('/semaine/{date}', name: 'app_formateur_planning_semaine', methods: ['GET'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
    public function semaine(string $date): Response
    {
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Date invalide.');
            return $this->redirectToRoute('app_formateur_planning_index');
        }
        
        // Trouver le lundi de la semaine
        $jourSemaine = (int) $dateObj->format('N');
        $lundi = (clone $dateObj)->modify('-' . ($jourSemaine - 1) . ' days');
        $dimanche = (clone $lundi)->modify('+6 days');
        
        // Semaines précédente et suivante
        $semainePrecedente = (clone $lundi)->modify('-7 days');
        $semaineSuivante = (clone $lundi)->modify('+7 days');
        
        // Récupérer les séances de la semaine
        $seances = $this->seanceRepo->findByFormateurAndPeriode($formateur, $lundi, $dimanche);
        
        // Regrouper par jour
        $seancesParJour = [];
        for ($i = 0; $i < 7; $i++) {
            $jour = (clone $lundi)->modify("+{$i} days");
            $dateStr = $jour->format('Y-m-d');
            $seancesParJour[$dateStr] = [
                'date' => clone $jour,
                'nomJour' => $this->getNomJourFr($jour->format('N')),
                'seances' => [],
            ];
        }
        
        foreach ($seances as $seance) {
            $dateStr = $seance->getDate()->format('Y-m-d');
            if (isset($seancesParJour[$dateStr])) {
                $seancesParJour[$dateStr]['seances'][] = $seance;
            }
        }
        
        // Statistiques
        $stats = [
            'total' => count($seances),
            'heures' => 0,
        ];
        foreach ($seances as $seance) {
            if ($seance->getStatut() !== StatutSeance::ANNULEE) {
                $stats['heures'] += $seance->getDureeMinutes() / 60;
            }
        }
        $stats['heures'] = round($stats['heures'], 1);
        
        return $this->render('formateur/planning/semaine.html.twig', [
            'lundi' => $lundi,
            'dimanche' => $dimanche,
            'seancesParJour' => $seancesParJour,
            'semainePrecedente' => $semainePrecedente,
            'semaineSuivante' => $semaineSuivante,
            'stats' => $stats,
            'formateur' => $formateur,
            'aujourdhui' => new \DateTime('today'),
        ]);
    }

    /**
     * Vérifie que le formateur a accès à cette séance
     */
    private function checkAccess(SeancePlanifiee $seance): void
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Admin a tous les droits
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Le formateur doit être assigné à cette séance
        if ($seance->getFormateurs()->contains($user)) {
            return;
        }
        
        // Ou être formateur de la session
        $session = $seance->getSession();
        if ($session && $session->getFormateurs()->contains($user)) {
            return;
        }
        
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette séance.');
    }

    /**
     * Construit la grille du calendrier mensuel
     */
    private function construireCalendrier(int $annee, int $mois, array $seancesParJour): array
    {
        $premierJour = new \DateTime(sprintf('%d-%02d-01', $annee, $mois));
        $dernierJour = (clone $premierJour)->modify('last day of this month');
        
        // Numéro du jour de la semaine du 1er (1=lundi, 7=dimanche)
        $jourSemainePremier = (int) $premierJour->format('N');
        $nbJoursMois = (int) $dernierJour->format('d');
        
        $semaines = [];
        $semaineCourante = [];
        
        // Remplir les jours vides avant le 1er du mois
        for ($i = 1; $i < $jourSemainePremier; $i++) {
            $semaineCourante[] = null;
        }
        
        // Remplir les jours du mois
        for ($jour = 1; $jour <= $nbJoursMois; $jour++) {
            $dateStr = sprintf('%d-%02d-%02d', $annee, $mois, $jour);
            $dateObj = new \DateTime($dateStr);
            
            $semaineCourante[] = [
                'jour' => $jour,
                'date' => $dateObj,
                'dateStr' => $dateStr,
                'nbSeances' => isset($seancesParJour[$dateStr]) ? count($seancesParJour[$dateStr]) : 0,
                'estAujourdhui' => $dateObj->format('Y-m-d') === date('Y-m-d'),
                'estWeekend' => in_array($dateObj->format('N'), ['6', '7']),
            ];
            
            // Nouvelle semaine si dimanche
            if (count($semaineCourante) === 7) {
                $semaines[] = $semaineCourante;
                $semaineCourante = [];
            }
        }
        
        // Compléter la dernière semaine si nécessaire
        while (count($semaineCourante) > 0 && count($semaineCourante) < 7) {
            $semaineCourante[] = null;
        }
        if (!empty($semaineCourante)) {
            $semaines[] = $semaineCourante;
        }
        
        return $semaines;
    }

    /**
     * Retourne le nom du mois en français
     */
    private function getNomMoisFr(int $mois): string
    {
        $noms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        return $noms[$mois] ?? '';
    }

    /**
     * Retourne le nom du jour en français
     */
    private function getNomJourFr(int $numeroJour): string
    {
        $noms = [
            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
            5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
        ];
        return $noms[$numeroJour] ?? '';
    }
}
