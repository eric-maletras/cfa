<?php

namespace App\Repository;

use App\Entity\Appel;
use App\Entity\SeancePlanifiee;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Appel
 *
 * @extends ServiceEntityRepository<Appel>
 */
class AppelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appel::class);
    }

    /**
     * Trouve l'appel actif (non clôturé) pour une séance
     */
    public function findActiveBySeance(SeancePlanifiee $seance): ?Appel
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.seance = :seance')
            ->andWhere('a.cloture = false')
            ->setParameter('seance', $seance)
            ->orderBy('a.dateAppel', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les appels pour une séance
     */
    public function findBySeance(SeancePlanifiee $seance): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.seance = :seance')
            ->setParameter('seance', $seance)
            ->orderBy('a.dateAppel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les appels effectués par un formateur
     */
    public function findByFormateur(User $formateur, ?\DateTimeInterface $dateDebut = null, ?\DateTimeInterface $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.formateur = :formateur')
            ->setParameter('formateur', $formateur)
            ->orderBy('a.dateAppel', 'DESC');

        if ($dateDebut) {
            $qb->andWhere('a.dateAppel >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('a.dateAppel <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les appels avec des liens expirés non clôturés
     * (pour le traitement automatique des non-signés)
     */
    public function findExpiredNonClotures(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.cloture = false')
            ->andWhere('a.dateExpiration < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les appels en attente de signatures pour une session
     */
    public function findEnAttenteBySession(int $sessionId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.seance', 's')
            ->andWhere('s.session = :sessionId')
            ->andWhere('a.cloture = false')
            ->andWhere('a.emailsEnvoyes = true')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('a.dateAppel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les appels du jour pour un formateur
     */
    public function countTodayByFormateur(User $formateur): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.formateur = :formateur')
            ->andWhere('a.dateAppel >= :today')
            ->andWhere('a.dateAppel < :tomorrow')
            ->setParameter('formateur', $formateur)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les statistiques d'appels pour une période
     */
    public function getStatistiques(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id) as total')
            ->addSelect('SUM(CASE WHEN a.cloture = true THEN 1 ELSE 0 END) as clotures')
            ->addSelect('SUM(CASE WHEN a.emailsEnvoyes = true THEN 1 ELSE 0 END) as avecEmails')
            ->andWhere('a.dateAppel >= :dateDebut')
            ->andWhere('a.dateAppel <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Sauvegarde un appel
     */
    public function save(Appel $appel, bool $flush = false): void
    {
        $this->getEntityManager()->persist($appel);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un appel
     */
    public function remove(Appel $appel, bool $flush = false): void
    {
        $this->getEntityManager()->remove($appel);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
