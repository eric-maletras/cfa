<?php

namespace App\Controller;

use App\Entity\Devoir;
use App\Entity\Note;
use App\Entity\Session;
use App\Entity\User;
use App\Form\DevoirType;
use App\Form\SaisieNotesType;
use App\Repository\DevoirRepository;
use App\Repository\InscriptionRepository;
use App\Repository\NoteRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des devoirs et notes
 * Accessible aux formateurs et administrateurs
 */
#[Route('/module/formateur_notes')]
#[IsGranted('ROLE_FORMATEUR')]
class DevoirController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private DevoirRepository $devoirRepo,
        private NoteRepository $noteRepo,
        private SessionRepository $sessionRepo,
        private InscriptionRepository $inscriptionRepo
    ) {}

    /**
     * Liste des devoirs du formateur connecté
     */
    #[Route('', name: 'app_formateur_notes_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        $devoirs = $this->devoirRepo->findByFormateur($formateur);
        
        // Statistiques par session
        $sessionsDevoirs = [];
        foreach ($devoirs as $devoir) {
            $sessionId = $devoir->getSession()->getId();
            if (!isset($sessionsDevoirs[$sessionId])) {
                $sessionsDevoirs[$sessionId] = [
                    'session' => $devoir->getSession(),
                    'devoirs' => [],
                ];
            }
            $sessionsDevoirs[$sessionId]['devoirs'][] = $devoir;
        }
        
        return $this->render('devoir/index.html.twig', [
            'sessionsDevoirs' => $sessionsDevoirs,
            'totalDevoirs' => count($devoirs),
        ]);
    }

    /**
     * Liste des devoirs d'une session
     */
    #[Route('/session/{id}', name: 'app_formateur_notes_session', methods: ['GET'])]
    public function parSession(Session $session): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le formateur intervient sur cette session
        if (!$this->isGranted('ROLE_ADMIN') && 
            !$session->getFormateurs()->contains($user) && 
            $session->getResponsable() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'intervenez pas sur cette session.');
        }
        
        $devoirs = $this->devoirRepo->findBySessionWithNotes($session);
        
        // Statistiques
        $stats = [
            'total' => count($devoirs),
            'avec_notes' => 0,
            'complets' => 0,
        ];
        
        foreach ($devoirs as $devoir) {
            if ($devoir->getNombreNotesSaisies() > 0) {
                $stats['avec_notes']++;
            }
            if ($devoir->isComplet()) {
                $stats['complets']++;
            }
        }
        
        return $this->render('devoir/session.html.twig', [
            'session' => $session,
            'devoirs' => $devoirs,
            'stats' => $stats,
        ]);
    }

    /**
     * Création d'un nouveau devoir
     */
    #[Route('/new', name: 'app_formateur_notes_new', methods: ['GET', 'POST'])]
    #[Route('/new/session/{sessionId}', name: 'app_formateur_notes_new_session', methods: ['GET', 'POST'])]
    public function new(Request $request, ?int $sessionId = null): Response
    {
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        $devoir = new Devoir();
        $devoir->setFormateur($formateur);
        
        // Si session pré-sélectionnée
        $session = null;
        if ($sessionId) {
            $session = $this->sessionRepo->find($sessionId);
            if ($session) {
                $devoir->setSession($session);
            }
        }
        
        $form = $this->createForm(DevoirType::class, $devoir, [
            'show_session_selector' => $session === null,
            'formateur' => $formateur,
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($devoir);
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'Devoir "%s" créé avec succès.',
                $devoir->getTitre()
            ));
            
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        return $this->render('devoir/form.html.twig', [
            'form' => $form,
            'devoir' => $devoir,
            'session' => $session,
            'title' => 'Nouveau devoir',
        ]);
    }

    /**
     * Affichage d'un devoir avec ses notes
     */
    #[Route('/{id}', name: 'app_formateur_notes_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        // Récupère ou crée les notes pour tous les apprenants
        $notes = $this->noteRepo->findOrCreateForDevoir($devoir);
        $this->em->flush(); // Persiste les nouvelles notes
        
        // Statistiques
        $stats = $this->noteRepo->getStatsByDevoir($devoir);
        
        return $this->render('devoir/show.html.twig', [
            'devoir' => $devoir,
            'notes' => $notes,
            'stats' => $stats,
        ]);
    }

    /**
     * Modification d'un devoir
     */
    #[Route('/{id}/edit', name: 'app_formateur_notes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        $form = $this->createForm(DevoirType::class, $devoir);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            
            $this->addFlash('success', sprintf(
                'Devoir "%s" modifié avec succès.',
                $devoir->getTitre()
            ));
            
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        return $this->render('devoir/form.html.twig', [
            'form' => $form,
            'devoir' => $devoir,
            'session' => $devoir->getSession(),
            'title' => 'Modifier le devoir',
        ]);
    }

    /**
     * Suppression d'un devoir
     */
    #[Route('/{id}/delete', name: 'app_formateur_notes_delete', methods: ['POST'])]
    public function delete(Request $request, Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        $session = $devoir->getSession();
        
        if ($this->isCsrfTokenValid('delete' . $devoir->getId(), $request->request->get('_token'))) {
            $titre = $devoir->getTitre();
            $this->em->remove($devoir);
            $this->em->flush();
            
            $this->addFlash('success', sprintf('Devoir "%s" supprimé.', $titre));
        }
        
        return $this->redirectToRoute('app_formateur_notes_session', ['id' => $session->getId()]);
    }

    /**
     * Saisie des notes en grille
     */
    #[Route('/{id}/notes', name: 'app_formateur_notes_notes', methods: ['GET', 'POST'])]
    public function saisieNotes(Request $request, Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        // Récupère ou crée les notes pour tous les apprenants
        $notes = $this->noteRepo->findOrCreateForDevoir($devoir);
        $this->em->flush();
        
        // Création du formulaire avec les notes
        $form = $this->createForm(SaisieNotesType::class, ['notes' => $notes]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour des métadonnées de chaque note
            foreach ($notes as $note) {
                if ($note->getValeur() !== null) {
                    $note->setSaisiePar($formateur);
                    $note->setDateSaisie(new \DateTime());
                }
            }
            
            // Gestion de la publication
            if ($form->get('publier')->getData()) {
                $devoir->setNotesPubliees(true);
            }
            
            $this->em->flush();
            
            $this->addFlash('success', 'Notes enregistrées avec succès.');
            
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        return $this->render('devoir/saisie_notes.html.twig', [
            'devoir' => $devoir,
            'notes' => $notes,
            'form' => $form,
        ]);
    }

    /**
     * Publication / dépublication des notes
     */
    #[Route('/{id}/toggle-publication', name: 'app_formateur_notes_toggle_publication', methods: ['POST'])]
    public function togglePublication(Request $request, Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        if ($this->isCsrfTokenValid('toggle' . $devoir->getId(), $request->request->get('_token'))) {
            $devoir->setNotesPubliees(!$devoir->isNotesPubliees());
            $this->em->flush();
            
            $statut = $devoir->isNotesPubliees() ? 'publiées' : 'masquées';
            $this->addFlash('success', sprintf('Notes %s.', $statut));
        }
        
        return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
    }

    /**
     * Affichage des moyennes d'une session
     */
    #[Route('/session/{id}/moyennes', name: 'app_formateur_notes_moyennes', methods: ['GET'])]
    public function moyennes(Session $session): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier l'accès
        if (!$this->isGranted('ROLE_ADMIN') && 
            !$session->getFormateurs()->contains($user) && 
            $session->getResponsable() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'intervenez pas sur cette session.');
        }
        
        // Calcul des moyennes
        $moyennes = $this->noteRepo->calculateMoyennesSession($session);
        
        // Devoirs de la session
        $devoirs = $this->devoirRepo->findBySession($session);
        
        // Statistiques globales
        $moyenneGenerale = null;
        $somme = 0;
        $count = 0;
        foreach ($moyennes as $m) {
            if ($m['moyenne'] !== null) {
                $somme += $m['moyenne'];
                $count++;
            }
        }
        if ($count > 0) {
            $moyenneGenerale = round($somme / $count, 2);
        }
        
        return $this->render('devoir/moyennes.html.twig', [
            'session' => $session,
            'moyennes' => $moyennes,
            'devoirs' => $devoirs,
            'moyenneGenerale' => $moyenneGenerale,
        ]);
    }

    /**
     * Duplication d'un devoir
     */
    #[Route('/{id}/duplicate', name: 'app_formateur_notes_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        if ($this->isCsrfTokenValid('duplicate' . $devoir->getId(), $request->request->get('_token'))) {
            // Création d'une copie
            $nouveauDevoir = new Devoir();
            $nouveauDevoir->setTitre($devoir->getTitre() . ' (copie)');
            $nouveauDevoir->setDescription($devoir->getDescription());
            $nouveauDevoir->setType($devoir->getType());
            $nouveauDevoir->setCoefficient($devoir->getCoefficient());
            $nouveauDevoir->setBareme($devoir->getBareme());
            $nouveauDevoir->setSession($devoir->getSession());
            $nouveauDevoir->setFormateur($this->getUser());
            $nouveauDevoir->setDateDevoir(new \DateTime());
            
            $this->em->persist($nouveauDevoir);
            $this->em->flush();
            
            $this->addFlash('success', 'Devoir dupliqué avec succès.');
            
            return $this->redirectToRoute('app_formateur_notes_edit', ['id' => $nouveauDevoir->getId()]);
        }
        
        return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
    }

    /**
     * Télécharger le template CSV pour l'import des notes
     */
    #[Route('/{id}/export-template', name: 'app_formateur_notes_export_template', methods: ['GET'])]
    public function exportTemplate(Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        // Récupère ou crée les notes pour tous les apprenants
        $notes = $this->noteRepo->findOrCreateForDevoir($devoir);
        $this->em->flush();
        
        // Génère le CSV
        $response = new StreamedResponse(function() use ($devoir, $notes) {
            $handle = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-tête
            fputcsv($handle, [
                'email',
                'nom',
                'prenom',
                'note',
                'statut',
                'commentaire'
            ], ';');
            
            // Lignes des apprenants
            foreach ($notes as $note) {
                $apprenant = $note->getApprenant();
                fputcsv($handle, [
                    $apprenant->getEmail(),
                    $apprenant->getNom(),
                    $apprenant->getPrenom(),
                    $note->getValeur() ?? '',
                    $note->getStatut(),
                    $note->getCommentaire() ?? ''
                ], ';');
            }
            
            fclose($handle);
        });
        
        // Nom du fichier
        $filename = sprintf(
            'notes_%s_%s.csv',
            $devoir->getSession()->getCode(),
            preg_replace('/[^a-zA-Z0-9]/', '_', $devoir->getTitre())
        );
        
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * Importer les notes depuis un fichier CSV
     */
    #[Route('/{id}/import-notes', name: 'app_formateur_notes_import', methods: ['POST'])]
    public function importNotes(Request $request, Devoir $devoir): Response
    {
        $this->checkAccess($devoir);
        
        if (!$this->isCsrfTokenValid('import' . $devoir->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        /** @var UploadedFile|null $file */
        $file = $request->files->get('csv_file');
        
        if (!$file || !$file->isValid()) {
            $this->addFlash('error', 'Fichier invalide ou non uploadé.');
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        // Vérifier l'extension
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), ['csv', 'txt'])) {
            $this->addFlash('error', 'Format de fichier non supporté. Utilisez un fichier CSV.');
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        /** @var User $formateur */
        $formateur = $this->getUser();
        
        // Récupère les notes existantes indexées par email
        $notes = $this->noteRepo->findOrCreateForDevoir($devoir);
        $notesByEmail = [];
        foreach ($notes as $note) {
            $notesByEmail[strtolower($note->getApprenant()->getEmail())] = $note;
        }
        
        // Lecture du fichier CSV
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            $this->addFlash('error', 'Impossible de lire le fichier.');
            return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
        }
        
        // Détection du délimiteur (virgule ou point-virgule)
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        
        $imported = 0;
        $errors = [];
        $lineNumber = 0;
        
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;
            
            // Ignorer la ligne d'en-tête
            if ($lineNumber === 1 && (stripos($row[0] ?? '', 'email') !== false || stripos($row[0] ?? '', 'mail') !== false)) {
                continue;
            }
            
            // Vérifier qu'on a au moins 4 colonnes (email, nom, prénom, note)
            if (count($row) < 4) {
                $errors[] = "Ligne $lineNumber : nombre de colonnes insuffisant";
                continue;
            }
            
            $email = trim($row[0] ?? '');
            // $nom = trim($row[1] ?? '');  // Non utilisé pour l'import, juste pour référence
            // $prenom = trim($row[2] ?? '');
            $noteValue = trim($row[3] ?? '');
            $statut = trim($row[4] ?? 'normal');
            $commentaire = trim($row[5] ?? '');
            
            if (empty($email)) {
                continue; // Ligne vide
            }
            
            // Trouver la note correspondante
            $emailLower = strtolower($email);
            if (!isset($notesByEmail[$emailLower])) {
                $errors[] = "Ligne $lineNumber : email '$email' non trouvé dans la session";
                continue;
            }
            
            $note = $notesByEmail[$emailLower];
            
            // Validation du statut
            $validStatuts = [Note::STATUT_NORMAL, Note::STATUT_ABSENT, Note::STATUT_DISPENSE, Note::STATUT_RATTRAPAGE];
            if (!empty($statut) && !in_array($statut, $validStatuts)) {
                $statut = Note::STATUT_NORMAL;
            }
            
            // Mise à jour de la note
            if ($statut === Note::STATUT_ABSENT || $statut === Note::STATUT_DISPENSE) {
                $note->setValeur(null);
            } elseif ($noteValue !== '') {
                // Conversion de la note (gère les virgules françaises)
                $noteValue = str_replace(',', '.', $noteValue);
                if (is_numeric($noteValue)) {
                    $valeur = (float) $noteValue;
                    // Vérification du barème
                    if ($valeur < 0 || $valeur > $devoir->getBaremeFloat()) {
                        $errors[] = "Ligne $lineNumber : note '$valeur' hors barème (0-{$devoir->getBareme()})";
                        continue;
                    }
                    $note->setValeur((string) $valeur);
                } else {
                    $errors[] = "Ligne $lineNumber : valeur de note invalide '$noteValue'";
                    continue;
                }
            }
            
            $note->setStatut($statut ?: Note::STATUT_NORMAL);
            if (!empty($commentaire)) {
                $note->setCommentaire($commentaire);
            }
            $note->setSaisiePar($formateur);
            $note->setDateSaisie(new \DateTime());
            
            $imported++;
        }
        
        fclose($handle);
        
        $this->em->flush();
        
        // Messages de retour
        if ($imported > 0) {
            $this->addFlash('success', "$imported note(s) importée(s) avec succès.");
        }
        
        if (!empty($errors)) {
            $errorCount = count($errors);
            $errorMsg = "$errorCount erreur(s) lors de l'import :\n" . implode("\n", array_slice($errors, 0, 5));
            if ($errorCount > 5) {
                $errorMsg .= "\n... et " . ($errorCount - 5) . " autre(s) erreur(s)";
            }
            $this->addFlash('warning', $errorMsg);
        }
        
        return $this->redirectToRoute('app_formateur_notes_show', ['id' => $devoir->getId()]);
    }

    /**
     * Vérifie les droits d'accès à un devoir
     */
    private function checkAccess(Devoir $devoir): void
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Admin a tous les droits
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Le formateur créateur a accès
        if ($devoir->getFormateur() === $user) {
            return;
        }
        
        // Les formateurs de la session ont accès
        $session = $devoir->getSession();
        if ($session && ($session->getFormateurs()->contains($user) || $session->getResponsable() === $user)) {
            return;
        }
        
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devoir.');
    }
}
