<?php

namespace App\Repository;

use App\Entity\Devoir;
use App\Entity\Note;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Note
 * 
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Récupère les notes d'un devoir avec les apprenants
     * 
     * @return Note[]
     */
    public function findByDevoir(Devoir $devoir): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.apprenant', 'a')
            ->addSelect('a')
            ->andWhere('n.devoir = :devoir')
            ->setParameter('devoir', $devoir)
            ->orderBy('a.nom', 'ASC')
            ->addOrderBy('a.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notes d'un apprenant
     * 
     * @return Note[]
     */
    public function findByApprenant(User $apprenant): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.devoir', 'd')
            ->addSelect('d')
            ->leftJoin('d.session', 's')
            ->addSelect('s')
            ->andWhere('n.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notes d'un apprenant pour une session
     * 
     * @return Note[]
     */
    public function findByApprenantAndSession(User $apprenant, Session $session): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.devoir', 'd')
            ->addSelect('d')
            ->andWhere('n.apprenant = :apprenant')
            ->andWhere('d.session = :session')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('session', $session)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la note d'un apprenant pour un devoir
     */
    public function findOneByDevoirAndApprenant(Devoir $devoir, User $apprenant): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.devoir = :devoir')
            ->andWhere('n.apprenant = :apprenant')
            ->setParameter('devoir', $devoir)
            ->setParameter('apprenant', $apprenant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule la moyenne d'un apprenant pour une session (moyenne pondérée)
     */
    public function calculateMoyenneApprenant(User $apprenant, Session $session): ?float
    {
        // Récupère toutes les notes de l'apprenant pour la session
        $notes = $this->createQueryBuilder('n')
            ->leftJoin('n.devoir', 'd')
            ->andWhere('n.apprenant = :apprenant')
            ->andWhere('d.session = :session')
            ->andWhere('n.valeur IS NOT NULL')
            ->andWhere('n.statut NOT IN (:exclus)')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('session', $session)
            ->setParameter('exclus', [Note::STATUT_ABSENT, Note::STATUT_DISPENSE])
            ->getQuery()
            ->getResult();
        
        if (empty($notes)) {
            return null;
        }
        
        $somme = 0;
        $totalCoef = 0;
        
        /** @var Note $note */
        foreach ($notes as $note) {
            $devoir = $note->getDevoir();
            $bareme = $devoir->getBaremeFloat();
            $coef = $devoir->getCoefficientFloat();
            
            // Ramène la note sur 20 avant de pondérer
            $noteSur20 = ($note->getValeurFloat() / $bareme) * 20;
            
            $somme += $noteSur20 * $coef;
            $totalCoef += $coef;
        }
        
        if ($totalCoef == 0) {
            return null;
        }
        
        return round($somme / $totalCoef, 2);
    }

    /**
     * Calcule les moyennes de tous les apprenants d'une session
     * 
     * @return array<int, array{user: User, moyenne: float|null, notes_count: int}>
     */
    public function calculateMoyennesSession(Session $session): array
    {
        // Récupère les apprenants inscrits à la session
        $em = $this->getEntityManager();
        
        $apprenants = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.inscriptions', 'i')
            ->andWhere('i.session = :session')
            ->andWhere('i.statut = :statut')
            ->setParameter('session', $session)
            ->setParameter('statut', 'validee')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
        
        $resultats = [];
        
        foreach ($apprenants as $apprenant) {
            $moyenne = $this->calculateMoyenneApprenant($apprenant, $session);
            
            // Compte les notes saisies
            $notesCount = $this->createQueryBuilder('n')
                ->select('COUNT(n.id)')
                ->leftJoin('n.devoir', 'd')
                ->andWhere('n.apprenant = :apprenant')
                ->andWhere('d.session = :session')
                ->andWhere('n.valeur IS NOT NULL')
                ->setParameter('apprenant', $apprenant)
                ->setParameter('session', $session)
                ->getQuery()
                ->getSingleScalarResult();
            
            $resultats[] = [
                'user' => $apprenant,
                'moyenne' => $moyenne,
                'notes_count' => (int) $notesCount,
            ];
        }
        
        return $resultats;
    }

    /**
     * Récupère les statistiques d'un devoir
     * 
     * @return array{moyenne: float|null, min: float|null, max: float|null, count: int, total: int}
     */
    public function getStatsByDevoir(Devoir $devoir): array
    {
        $result = $this->createQueryBuilder('n')
            ->select('AVG(n.valeur) as moyenne, MIN(n.valeur) as min, MAX(n.valeur) as max, COUNT(n.id) as total')
            ->andWhere('n.devoir = :devoir')
            ->andWhere('n.valeur IS NOT NULL')
            ->andWhere('n.statut NOT IN (:exclus)')
            ->setParameter('devoir', $devoir)
            ->setParameter('exclus', [Note::STATUT_ABSENT, Note::STATUT_DISPENSE])
            ->getQuery()
            ->getSingleResult();
        
        $totalInscrits = $devoir->getSession()?->getNombreInscrits() ?? 0;
        
        return [
            'moyenne' => $result['moyenne'] !== null ? round((float) $result['moyenne'], 2) : null,
            'min' => $result['min'] !== null ? (float) $result['min'] : null,
            'max' => $result['max'] !== null ? (float) $result['max'] : null,
            'count' => (int) $result['total'],
            'total' => $totalInscrits,
        ];
    }

    /**
     * Compte les notes par statut pour un devoir
     * 
     * @return array<string, int>
     */
    public function countByStatutForDevoir(Devoir $devoir): array
    {
        $results = $this->createQueryBuilder('n')
            ->select('n.statut, COUNT(n.id) as total')
            ->andWhere('n.devoir = :devoir')
            ->setParameter('devoir', $devoir)
            ->groupBy('n.statut')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach (Note::STATUTS as $code => $libelle) {
            $counts[$code] = 0;
        }
        foreach ($results as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }
        
        return $counts;
    }

    /**
     * Récupère les notes récentes saisies par un formateur
     * 
     * @return Note[]
     */
    public function findRecentBySaisiePar(User $formateur, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.devoir', 'd')
            ->addSelect('d')
            ->leftJoin('n.apprenant', 'a')
            ->addSelect('a')
            ->andWhere('n.saisiePar = :formateur')
            ->setParameter('formateur', $formateur)
            ->orderBy('n.dateSaisie', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère ou crée les notes pour un devoir (pour tous les apprenants inscrits)
     * 
     * @return Note[]
     */
    public function findOrCreateForDevoir(Devoir $devoir): array
    {
        $session = $devoir->getSession();
        if (!$session) {
            return [];
        }
        
        $em = $this->getEntityManager();
        
        // Récupère les apprenants inscrits
        $apprenants = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.inscriptions', 'i')
            ->andWhere('i.session = :session')
            ->andWhere('i.statut = :statut')
            ->setParameter('session', $session)
            ->setParameter('statut', 'validee')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Notes existantes indexées par ID d'apprenant
        $notesExistantes = [];
        foreach ($this->findByDevoir($devoir) as $note) {
            $notesExistantes[$note->getApprenant()->getId()] = $note;
        }
        
        $notes = [];
        foreach ($apprenants as $apprenant) {
            if (isset($notesExistantes[$apprenant->getId()])) {
                $notes[] = $notesExistantes[$apprenant->getId()];
            } else {
                // Crée une nouvelle note vide
                $note = new Note();
                $note->setDevoir($devoir);
                $note->setApprenant($apprenant);
                $em->persist($note);
                $notes[] = $note;
            }
        }
        
        return $notes;
    }

    /**
     * Vérifie si un apprenant a des notes dans une session
     */
    public function hasNotesInSession(User $apprenant, Session $session): bool
    {
        $count = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->leftJoin('n.devoir', 'd')
            ->andWhere('n.apprenant = :apprenant')
            ->andWhere('d.session = :session')
            ->andWhere('n.valeur IS NOT NULL')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $count > 0;
    }

    /**
     * Récupère le bulletin de notes d'un apprenant pour une session
     * 
     * @return array<int, array{devoir: Devoir, note: Note|null}>
     */
    public function getBulletin(User $apprenant, Session $session): array
    {
        $em = $this->getEntityManager();
        
        // Récupère tous les devoirs visibles de la session
        $devoirs = $em->getRepository(Devoir::class)->findVisiblesBySession($session);
        
        // Notes de l'apprenant indexées par ID de devoir
        $notesApprenant = [];
        foreach ($this->findByApprenantAndSession($apprenant, $session) as $note) {
            $notesApprenant[$note->getDevoir()->getId()] = $note;
        }
        
        $bulletin = [];
        foreach ($devoirs as $devoir) {
            $bulletin[] = [
                'devoir' => $devoir,
                'note' => $notesApprenant[$devoir->getId()] ?? null,
            ];
        }
        
        return $bulletin;
    }
}
