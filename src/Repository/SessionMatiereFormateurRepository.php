<?php

namespace App\Repository;

use App\Entity\Session;
use App\Entity\SessionMatiere;
use App\Entity\SessionMatiereFormateur;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionMatiereFormateur>
 */
class SessionMatiereFormateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionMatiereFormateur::class);
    }

    /**
     * Trouve toutes les assignations pour une matière de session
     * 
     * @return SessionMatiereFormateur[]
     */
    public function findBySessionMatiere(SessionMatiere $sessionMatiere): array
    {
        return $this->createQueryBuilder('smf')
            ->andWhere('smf.sessionMatiere = :sm')
            ->setParameter('sm', $sessionMatiere)
            ->leftJoin('smf.formateur', 'f')
            ->addSelect('f')
            ->orderBy('smf.estResponsable', 'DESC')
            ->addOrderBy('f.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les assignations d'un formateur dans une session
     * 
     * @return SessionMatiereFormateur[]
     */
    public function findByFormateurAndSession(User $formateur, Session $session): array
    {
        return $this->createQueryBuilder('smf')
            ->join('smf.sessionMatiere', 'sm')
            ->andWhere('smf.formateur = :formateur')
            ->andWhere('sm.session = :session')
            ->setParameter('formateur', $formateur)
            ->setParameter('session', $session)
            ->leftJoin('sm.matiere', 'm')
            ->addSelect('sm', 'm')
            ->orderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les assignations pour une session avec eager loading
     * 
     * @return SessionMatiereFormateur[]
     */
    public function findBySessionWithDetails(Session $session): array
    {
        return $this->createQueryBuilder('smf')
            ->join('smf.sessionMatiere', 'sm')
            ->join('smf.formateur', 'f')
            ->join('sm.matiere', 'm')
            ->andWhere('sm.session = :session')
            ->setParameter('session', $session)
            ->addSelect('sm', 'f', 'm')
            ->orderBy('m.code', 'ASC')
            ->addOrderBy('smf.estResponsable', 'DESC')
            ->addOrderBy('f.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un formateur est déjà assigné à une matière de session
     */
    public function isFormateurAssigne(SessionMatiere $sessionMatiere, User $formateur): bool
    {
        $result = $this->createQueryBuilder('smf')
            ->select('COUNT(smf.id)')
            ->andWhere('smf.sessionMatiere = :sm')
            ->andWhere('smf.formateur = :formateur')
            ->setParameter('sm', $sessionMatiere)
            ->setParameter('formateur', $formateur)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result > 0;
    }

    /**
     * Trouve les formateurs disponibles pour une matière de session
     * (formateurs de la session non encore assignés à cette matière)
     * 
     * @return User[]
     */
    public function findFormateursDisponibles(SessionMatiere $sessionMatiere): array
    {
        $session = $sessionMatiere->getSession();
        if (!$session) {
            return [];
        }

        // Récupérer les IDs des formateurs déjà assignés
        $assignes = $this->createQueryBuilder('smf')
            ->select('IDENTITY(smf.formateur)')
            ->andWhere('smf.sessionMatiere = :sm')
            ->setParameter('sm', $sessionMatiere)
            ->getQuery()
            ->getSingleColumnResult();

        // Récupérer les formateurs de la session qui ne sont pas assignés
        $formateursSession = $session->getFormateurs()->toArray();
        
        if (empty($assignes)) {
            return $formateursSession;
        }

        return array_filter(
            $formateursSession,
            fn(User $f) => !in_array($f->getId(), $assignes)
        );
    }

    /**
     * Calcule les statistiques d'assignation pour une session
     */
    public function getStatistiquesSession(Session $session): array
    {
        // Nombre total de matières
        $qbMatieres = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(sm.id)')
            ->from(SessionMatiere::class, 'sm')
            ->where('sm.session = :session')
            ->andWhere('sm.actif = true')
            ->setParameter('session', $session);
        $totalMatieres = (int) $qbMatieres->getQuery()->getSingleScalarResult();

        // Nombre de matières avec au moins un formateur
        $qbAvecFormateur = $this->createQueryBuilder('smf')
            ->select('COUNT(DISTINCT smf.sessionMatiere)')
            ->join('smf.sessionMatiere', 'sm')
            ->where('sm.session = :session')
            ->andWhere('sm.actif = true')
            ->setParameter('session', $session);
        $matieresAvecFormateur = (int) $qbAvecFormateur->getQuery()->getSingleScalarResult();

        // Nombre total d'assignations
        $qbAssignations = $this->createQueryBuilder('smf')
            ->select('COUNT(smf.id)')
            ->join('smf.sessionMatiere', 'sm')
            ->where('sm.session = :session')
            ->setParameter('session', $session);
        $totalAssignations = (int) $qbAssignations->getQuery()->getSingleScalarResult();

        // Total heures assignées
        $qbHeures = $this->createQueryBuilder('smf')
            ->select('SUM(smf.heuresAssignees)')
            ->join('smf.sessionMatiere', 'sm')
            ->where('sm.session = :session')
            ->setParameter('session', $session);
        $totalHeuresAssignees = (int) ($qbHeures->getQuery()->getSingleScalarResult() ?? 0);

        // Formateurs distincts intervenant
        $qbFormateurs = $this->createQueryBuilder('smf')
            ->select('COUNT(DISTINCT smf.formateur)')
            ->join('smf.sessionMatiere', 'sm')
            ->where('sm.session = :session')
            ->setParameter('session', $session);
        $formateursIntervenants = (int) $qbFormateurs->getQuery()->getSingleScalarResult();

        return [
            'totalMatieres' => $totalMatieres,
            'matieresAvecFormateur' => $matieresAvecFormateur,
            'matieresSansFormateur' => $totalMatieres - $matieresAvecFormateur,
            'totalAssignations' => $totalAssignations,
            'totalHeuresAssignees' => $totalHeuresAssignees,
            'formateursIntervenants' => $formateursIntervenants,
            'tauxCouverture' => $totalMatieres > 0 
                ? round(($matieresAvecFormateur / $totalMatieres) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Retourne un tableau indexé par SessionMatiere ID avec les formateurs
     * Utile pour l'affichage dans les listes
     * 
     * @return array<int, SessionMatiereFormateur[]>
     */
    public function getFormateursGroupedBySessionMatiere(Session $session): array
    {
        $assignations = $this->findBySessionWithDetails($session);
        
        $grouped = [];
        foreach ($assignations as $smf) {
            $smId = $smf->getSessionMatiere()->getId();
            if (!isset($grouped[$smId])) {
                $grouped[$smId] = [];
            }
            $grouped[$smId][] = $smf;
        }
        
        return $grouped;
    }

    /**
     * Retire le statut responsable des autres formateurs d'une matière
     * (pour s'assurer qu'il n'y a qu'un seul responsable)
     */
    public function clearResponsable(SessionMatiere $sessionMatiere, ?int $exceptId = null): void
    {
        $qb = $this->createQueryBuilder('smf')
            ->update()
            ->set('smf.estResponsable', 'false')
            ->where('smf.sessionMatiere = :sm')
            ->setParameter('sm', $sessionMatiere);
        
        if ($exceptId !== null) {
            $qb->andWhere('smf.id != :exceptId')
               ->setParameter('exceptId', $exceptId);
        }
        
        $qb->getQuery()->execute();
    }
}
