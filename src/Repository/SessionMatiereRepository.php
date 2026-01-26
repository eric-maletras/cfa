<?php

namespace App\Repository;

use App\Entity\Matiere;
use App\Entity\Session;
use App\Entity\SessionMatiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité SessionMatiere
 *
 * @extends ServiceEntityRepository<SessionMatiere>
 */
class SessionMatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionMatiere::class);
    }

    /**
     * Retourne les matières d'une session, triées par ordre
     *
     * @param Session $session
     * @param bool $activeOnly Ne retourner que les matières actives
     * @return SessionMatiere[]
     */
    public function findBySession(Session $session, bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('sm')
            ->andWhere('sm.session = :session')
            ->setParameter('session', $session)
            ->leftJoin('sm.matiere', 'm')
            ->addSelect('m')
            ->orderBy('sm.ordre', 'ASC')
            ->addOrderBy('m.code', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('sm.actif = :actif')
               ->setParameter('actif', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Vérifie si une liaison session-matière existe déjà
     */
    public function findBySessionAndMatiere(Session $session, Matiere $matiere): ?SessionMatiere
    {
        return $this->createQueryBuilder('sm')
            ->andWhere('sm.session = :session')
            ->andWhere('sm.matiere = :matiere')
            ->setParameter('session', $session)
            ->setParameter('matiere', $matiere)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne le prochain ordre disponible pour une session
     */
    public function getNextOrdre(Session $session): int
    {
        $maxOrdre = $this->createQueryBuilder('sm')
            ->select('MAX(sm.ordre)')
            ->andWhere('sm.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxOrdre ?? -1) + 1;
    }

    /**
     * Retourne les statistiques des matières pour une session
     */
    public function getStatistiquesSession(Session $session): array
    {
        $result = $this->createQueryBuilder('sm')
            ->select('COUNT(sm.id) as nbMatieres')
            ->addSelect('SUM(sm.volumeHeuresReferentiel) as totalHeuresRef')
            ->addSelect('SUM(sm.volumeHeuresPlanifie) as totalHeuresPlan')
            ->addSelect('SUM(sm.volumeHeuresRealise) as totalHeuresReal')
            ->addSelect('SUM(CASE WHEN sm.actif = true THEN 1 ELSE 0 END) as nbActives')
            ->andWhere('sm.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleResult();

        return [
            'nbMatieres' => (int) $result['nbMatieres'],
            'nbActives' => (int) $result['nbActives'],
            'totalHeuresRef' => (int) ($result['totalHeuresRef'] ?? 0),
            'totalHeuresPlan' => $result['totalHeuresPlan'] ? (int) $result['totalHeuresPlan'] : null,
            'totalHeuresReal' => $result['totalHeuresReal'] ? (int) $result['totalHeuresReal'] : null,
        ];
    }

    /**
     * Calcule le volume horaire total planifié pour une session (actives uniquement)
     */
    public function getTotalVolumeHeuresPlanifie(Session $session): int
    {
        $result = $this->createQueryBuilder('sm')
            ->select('SUM(COALESCE(sm.volumeHeuresPlanifie, sm.volumeHeuresReferentiel)) as total')
            ->andWhere('sm.session = :session')
            ->andWhere('sm.actif = :actif')
            ->setParameter('session', $session)
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Calcule le volume horaire total réalisé pour une session
     */
    public function getTotalVolumeHeuresRealise(Session $session): int
    {
        $result = $this->createQueryBuilder('sm')
            ->select('SUM(sm.volumeHeuresRealise) as total')
            ->andWhere('sm.session = :session')
            ->andWhere('sm.actif = :actif')
            ->setParameter('session', $session)
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Retourne les matières non encore dans la session (pour ajout hors référentiel)
     */
    public function findMatieresNotInSession(Session $session): array
    {
        $em = $this->getEntityManager();
        
        $subQb = $em->createQueryBuilder();
        $subQb->select('IDENTITY(sm.matiere)')
            ->from(SessionMatiere::class, 'sm')
            ->where('sm.session = :session');

        $qb = $em->createQueryBuilder();
        return $qb->select('m')
            ->from(Matiere::class, 'm')
            ->where($qb->expr()->notIn('m.id', $subQb->getDQL()))
            ->andWhere('m.actif = :actif')
            ->setParameter('session', $session)
            ->setParameter('actif', true)
            ->orderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
