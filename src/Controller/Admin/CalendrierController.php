<?php

namespace App\Controller\Admin;

use App\Entity\CalendrierAnnee;
use App\Entity\JourFerme;
use App\Enum\TypeJourFerme;
use App\Form\CalendrierAnneeType;
use App\Form\JourFermeType;
use App\Repository\CalendrierAnneeRepository;
use App\Repository\JourFermeRepository;
use App\Repository\SeancePlanifieeRepository;
use App\Service\JoursFeriesFranceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur CRUD pour la gestion des calendriers annuels
 */
#[Route('/admin/calendriers')]
#[IsGranted('ROLE_ADMIN')]
class CalendrierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CalendrierAnneeRepository $calendrierRepository,
        private JourFermeRepository $jourFermeRepository,
        private JoursFeriesFranceService $joursFeriesService,
        private SeancePlanifieeRepository $seancePlanifieeRepository,
    ) {
    }

    /**
     * Liste des calendriers
     */
    #[Route('', name: 'admin_calendrier_index', methods: ['GET'])]
    public function index(): Response
    {
        $calendriers = $this->calendrierRepository->findAllOrdered();

        return $this->render('admin/calendrier/index.html.twig', [
            'calendriers' => $calendriers,
        ]);
    }

    /**
     * Création d'un nouveau calendrier
     */
    #[Route('/new', name: 'admin_calendrier_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $calendrier = new CalendrierAnnee();

        // Pré-remplissage intelligent basé sur l'année actuelle
        $now = new \DateTime();
        $moisActuel = (int) $now->format('n');
        $anneeActuelle = (int) $now->format('Y');

        if ($moisActuel >= 9) {
            // Si on est après septembre, on propose l'année courante/suivante
            $anneeDebut = $anneeActuelle;
        } else {
            // Sinon on propose l'année précédente/courante
            $anneeDebut = $anneeActuelle - 1;
        }
        $anneeFin = $anneeDebut + 1;

        $calendrier->setCode(sprintf('%d-%d', $anneeDebut, $anneeFin));
        $calendrier->setLibelle(sprintf('Année scolaire %d-%d', $anneeDebut, $anneeFin));
        $calendrier->setDateDebut(new \DateTime(sprintf('%d-09-01', $anneeDebut)));
        $calendrier->setDateFin(new \DateTime(sprintf('%d-07-31', $anneeFin))); // Fin en juillet

        $form = $this->createForm(CalendrierAnneeType::class, $calendrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($calendrier);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le calendrier "%s" a été créé avec succès.',
                $calendrier->getCode()
            ));

            return $this->redirectToRoute('admin_calendrier_show', ['id' => $calendrier->getId()]);
        }

        return $this->render('admin/calendrier/new.html.twig', [
            'calendrier' => $calendrier,
            'form' => $form,
        ]);
    }

    /**
     * Affichage détaillé d'un calendrier avec vue calendrier mensuelle
     */
    #[Route('/{id}', name: 'admin_calendrier_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CalendrierAnnee $calendrier, Request $request): Response
    {
        // Récupération du mois/année à afficher (par défaut : mois courant ou début du calendrier)
        $now = new \DateTime();
        $defaultYear = max(
            (int) $calendrier->getDateDebut()->format('Y'),
            min((int) $calendrier->getDateFin()->format('Y'), (int) $now->format('Y'))
        );
        $defaultMonth = $calendrier->contientDate($now) 
            ? (int) $now->format('n')
            : (int) $calendrier->getDateDebut()->format('n');

        $annee = (int) $request->query->get('annee', $defaultYear);
        $mois = (int) $request->query->get('mois', $defaultMonth);

        // Validation des paramètres
        $mois = max(1, min(12, $mois));

        // Récupération des jours fermés pour le mois
        $joursFermesMois = $this->jourFermeRepository->findByCalendrierAndMonth($calendrier, $annee, $mois);
        $joursParDate = [];
        foreach ($joursFermesMois as $jour) {
            $joursParDate[$jour->getDate()->format('Y-m-d')] = $jour;
        }

        // Récupération du nombre de séances par jour pour le mois
        $seancesParJour = $this->seancePlanifieeRepository->countByDateForMonth($annee, $mois);

        // Construction de la grille calendrier
        $calendrierData = $this->buildCalendrierMensuel($annee, $mois, $joursParDate, $calendrier, $seancesParJour);

        // Statistiques par type
        $countByType = $this->jourFermeRepository->countByTypeForCalendrier($calendrier);

        // Prochains jours fermés
        $prochainsFermes = $this->jourFermeRepository->findUpcoming($calendrier, 5);

        return $this->render('admin/calendrier/show.html.twig', [
            'calendrier' => $calendrier,
            'calendrierData' => $calendrierData,
            'annee' => $annee,
            'mois' => $mois,
            'countByType' => $countByType,
            'prochainsFermes' => $prochainsFermes,
            'types' => TypeJourFerme::cases(),
        ]);
    }

    /**
     * Modification d'un calendrier
     */
    #[Route('/{id}/edit', name: 'admin_calendrier_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, CalendrierAnnee $calendrier): Response
    {
        $form = $this->createForm(CalendrierAnneeType::class, $calendrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le calendrier "%s" a été modifié avec succès.',
                $calendrier->getCode()
            ));

            return $this->redirectToRoute('admin_calendrier_show', ['id' => $calendrier->getId()]);
        }

        return $this->render('admin/calendrier/edit.html.twig', [
            'calendrier' => $calendrier,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'un calendrier
     */
    #[Route('/{id}/delete', name: 'admin_calendrier_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, CalendrierAnnee $calendrier): Response
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete_calendrier_' . $calendrier->getId(), $token)) {
            $code = $calendrier->getCode();

            $this->entityManager->remove($calendrier);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le calendrier "%s" a été supprimé.',
                $code
            ));
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('admin_calendrier_index');
    }

    /**
     * Basculement du statut actif
     */
    #[Route('/{id}/toggle', name: 'admin_calendrier_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, CalendrierAnnee $calendrier): Response
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('toggle_calendrier_' . $calendrier->getId(), $token)) {
            $nouveauStatut = !$calendrier->isActif();
            $calendrier->setActif($nouveauStatut);

            // Si on active ce calendrier, désactiver les autres
            if ($nouveauStatut) {
                $this->calendrierRepository->desactiverAutres($calendrier);
            }

            $this->entityManager->flush();

            $status = $calendrier->isActif() ? 'activé' : 'désactivé';
            $this->addFlash('success', sprintf(
                'Le calendrier "%s" a été %s.',
                $calendrier->getCode(),
                $status
            ));
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('admin_calendrier_index');
    }

    /**
     * Import des jours fériés français
     */
    #[Route('/{id}/import-feries', name: 'admin_calendrier_import_feries', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function importFeries(Request $request, CalendrierAnnee $calendrier): Response
    {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('import_feries_' . $calendrier->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_calendrier_show', ['id' => $calendrier->getId()]);
        }

        // Récupérer les années couvertes par le calendrier
        $annees = $calendrier->getAnneesCouvertes();

        // Récupérer les jours fériés pour ces années
        $joursFeries = $this->joursFeriesService->getJoursFeriesPourAnnees($annees);

        // Récupérer les dates déjà enregistrées pour éviter les doublons
        $datesExistantes = [];
        foreach ($calendrier->getJoursFermes() as $jour) {
            $datesExistantes[$jour->getDate()->format('Y-m-d')] = true;
        }

        $nbImportes = 0;
        $nbIgnores = 0;

        foreach ($joursFeries as $ferie) {
            $dateStr = $ferie['date']->format('Y-m-d');

            // Vérifier que la date est dans la période du calendrier
            if (!$calendrier->contientDate($ferie['date'])) {
                continue;
            }

            // Vérifier si la date existe déjà
            if (isset($datesExistantes[$dateStr])) {
                $nbIgnores++;
                continue;
            }

            // Créer le jour férié
            $jourFerme = new JourFerme();
            $jourFerme->setCalendrier($calendrier);
            $jourFerme->setDate(\DateTime::createFromImmutable($ferie['date']));
            $jourFerme->setLibelle($ferie['libelle']);
            $jourFerme->setType(TypeJourFerme::FERIE);

            $this->entityManager->persist($jourFerme);
            $nbImportes++;
        }

        $this->entityManager->flush();

        if ($nbImportes > 0) {
            $this->addFlash('success', sprintf(
                '%d jour(s) férié(s) importé(s) avec succès.',
                $nbImportes
            ));
        }

        if ($nbIgnores > 0) {
            $this->addFlash('info', sprintf(
                '%d jour(s) ignoré(s) (déjà présents).',
                $nbIgnores
            ));
        }

        if ($nbImportes === 0 && $nbIgnores === 0) {
            $this->addFlash('warning', 'Aucun jour férié à importer pour cette période.');
        }

        return $this->redirectToRoute('admin_calendrier_show', ['id' => $calendrier->getId()]);
    }

    /**
     * Liste des jours fermés d'un calendrier
     */
    #[Route('/{id}/jours-fermes', name: 'admin_calendrier_jours_fermes', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function joursFermes(CalendrierAnnee $calendrier, Request $request): Response
    {
        $typeFilter = $request->query->get('type');

        if ($typeFilter) {
            $type = TypeJourFerme::tryFrom($typeFilter);
            $joursFermes = $type 
                ? $this->jourFermeRepository->findByCalendrierAndType($calendrier, $type)
                : $this->jourFermeRepository->findByCalendrierOrdered($calendrier);
        } else {
            $joursFermes = $this->jourFermeRepository->findByCalendrierOrdered($calendrier);
        }

        // Statistiques par type
        $countByType = $this->jourFermeRepository->countByTypeForCalendrier($calendrier);

        return $this->render('admin/calendrier/jours_fermes.html.twig', [
            'calendrier' => $calendrier,
            'joursFermes' => $joursFermes,
            'countByType' => $countByType,
            'types' => TypeJourFerme::cases(),
            'typeFilter' => $typeFilter,
        ]);
    }

    /**
     * Ajout d'un jour fermé
     */
    #[Route('/{id}/jours-fermes/new', name: 'admin_jour_ferme_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newJourFerme(Request $request, CalendrierAnnee $calendrier): Response
    {
        $jourFerme = new JourFerme();
        $jourFerme->setCalendrier($calendrier);

        $form = $this->createForm(JourFermeType::class, $jourFerme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la date est dans la période du calendrier
            if (!$calendrier->contientDate($jourFerme->getDate())) {
                $this->addFlash('error', sprintf(
                    'La date doit être comprise entre le %s et le %s.',
                    $calendrier->getDateDebut()->format('d/m/Y'),
                    $calendrier->getDateFin()->format('d/m/Y')
                ));
                return $this->render('admin/jour_ferme/new.html.twig', [
                    'calendrier' => $calendrier,
                    'form' => $form,
                ]);
            }

            $this->entityManager->persist($jourFerme);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le jour fermé "%s" a été ajouté.',
                $jourFerme->getLibelle()
            ));

            return $this->redirectToRoute('admin_calendrier_jours_fermes', ['id' => $calendrier->getId()]);
        }

        return $this->render('admin/jour_ferme/new.html.twig', [
            'calendrier' => $calendrier,
            'form' => $form,
        ]);
    }

    /**
     * Modification d'un jour fermé
     */
    #[Route('/jours-fermes/{id}/edit', name: 'admin_jour_ferme_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editJourFerme(Request $request, JourFerme $jourFerme): Response
    {
        $calendrier = $jourFerme->getCalendrier();
        
        $form = $this->createForm(JourFermeType::class, $jourFerme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la date est dans la période du calendrier
            if (!$calendrier->contientDate($jourFerme->getDate())) {
                $this->addFlash('error', sprintf(
                    'La date doit être comprise entre le %s et le %s.',
                    $calendrier->getDateDebut()->format('d/m/Y'),
                    $calendrier->getDateFin()->format('d/m/Y')
                ));
                return $this->render('admin/jour_ferme/edit.html.twig', [
                    'calendrier' => $calendrier,
                    'jourFerme' => $jourFerme,
                    'form' => $form,
                ]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le jour fermé "%s" a été modifié.',
                $jourFerme->getLibelle()
            ));

            return $this->redirectToRoute('admin_calendrier_jours_fermes', ['id' => $calendrier->getId()]);
        }

        return $this->render('admin/jour_ferme/edit.html.twig', [
            'calendrier' => $calendrier,
            'jourFerme' => $jourFerme,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'un jour fermé
     */
    #[Route('/jours-fermes/{id}/delete', name: 'admin_jour_ferme_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteJourFerme(Request $request, JourFerme $jourFerme): Response
    {
        $calendrier = $jourFerme->getCalendrier();
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete_jour_ferme_' . $jourFerme->getId(), $token)) {
            $libelle = $jourFerme->getLibelle();

            $this->entityManager->remove($jourFerme);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le jour fermé "%s" a été supprimé.',
                $libelle
            ));
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('admin_calendrier_jours_fermes', ['id' => $calendrier->getId()]);
    }

    /**
     * Construit les données pour l'affichage d'un mois du calendrier
     */
    private function buildCalendrierMensuel(
        int $annee,
        int $mois,
        array $joursParDate,
        CalendrierAnnee $calendrier,
        array $seancesParJour = []
    ): array {
        $premierJour = new \DateTimeImmutable(sprintf('%04d-%02d-01', $annee, $mois));
        $dernierJour = $premierJour->modify('last day of this month');

        // Nom du mois en français
        $nomsMois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        // Jour de la semaine du premier jour (1=lundi, 7=dimanche)
        $premierJourSemaine = (int) $premierJour->format('N');
        $nbJours = (int) $dernierJour->format('d');

        // Construction des semaines
        $semaines = [];
        $semaineCourante = array_fill(0, 7, null);
        $jourCourant = 1;

        // Remplir les jours vides au début
        for ($i = $premierJourSemaine - 1; $i < 7 && $jourCourant <= $nbJours; $i++) {
            $date = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $annee, $mois, $jourCourant));
            $dateStr = $date->format('Y-m-d');

            $semaineCourante[$i] = [
                'jour' => $jourCourant,
                'date' => $date,
                'dateStr' => $dateStr,
                'estWeekend' => $i >= 5,
                'estAujourdHui' => $dateStr === (new \DateTime())->format('Y-m-d'),
                'estDansPeriode' => $calendrier->contientDate($date),
                'jourFerme' => $joursParDate[$dateStr] ?? null,
                'nbSeances' => $seancesParJour[$dateStr] ?? 0,
            ];

            $jourCourant++;
        }

        $semaines[] = $semaineCourante;

        // Remplir les autres semaines
        while ($jourCourant <= $nbJours) {
            $semaineCourante = array_fill(0, 7, null);

            for ($i = 0; $i < 7 && $jourCourant <= $nbJours; $i++) {
                $date = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $annee, $mois, $jourCourant));
                $dateStr = $date->format('Y-m-d');

                $semaineCourante[$i] = [
                    'jour' => $jourCourant,
                    'date' => $date,
                    'dateStr' => $dateStr,
                    'estWeekend' => $i >= 5,
                    'estAujourdHui' => $dateStr === (new \DateTime())->format('Y-m-d'),
                    'estDansPeriode' => $calendrier->contientDate($date),
                    'jourFerme' => $joursParDate[$dateStr] ?? null,
                    'nbSeances' => $seancesParJour[$dateStr] ?? 0,
                ];

                $jourCourant++;
            }

            $semaines[] = $semaineCourante;
        }

        // Calcul du mois précédent/suivant
        $moisPrecedent = $mois === 1 ? 12 : $mois - 1;
        $anneePrecedente = $mois === 1 ? $annee - 1 : $annee;
        $moisSuivant = $mois === 12 ? 1 : $mois + 1;
        $anneeSuivante = $mois === 12 ? $annee + 1 : $annee;

        return [
            'nomMois' => $nomsMois[$mois],
            'annee' => $annee,
            'mois' => $mois,
            'semaines' => $semaines,
            'navigation' => [
                'precedent' => ['mois' => $moisPrecedent, 'annee' => $anneePrecedente],
                'suivant' => ['mois' => $moisSuivant, 'annee' => $anneeSuivante],
            ],
        ];
    }
}
