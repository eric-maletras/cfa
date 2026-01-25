<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\NiveauQualification;
use App\Entity\TypeCertification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Récupère toutes les formations actives
     * 
     * @return Formation[]
     */
    public function findAllActives(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('f.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formations par niveau de qualification
     * 
     * @return Formation[]
     */
    public function findByNiveau(NiveauQualification $niveau): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.niveauQualification = :niveau')
            ->andWhere('f.actif = :actif')
            ->setParameter('niveau', $niveau)
            ->setParameter('actif', true)
            ->orderBy('f.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formations par type de certification
     * 
     * @return Formation[]
     */
    public function findByTypeCertification(TypeCertification $type): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.typeCertification = :type')
            ->andWhere('f.actif = :actif')
            ->setParameter('type', $type)
            ->setParameter('actif', true)
            ->orderBy('f.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche une formation par son code RNCP
     */
    public function findByCodeRncp(string $codeRncp): ?Formation
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.codeRncp = :code')
            ->setParameter('code', $codeRncp)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche par mot-clé dans l'intitulé
     * 
     * @return Formation[]
     */
    public function search(string $search): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('LOWER(f.intitule) LIKE LOWER(:search) OR LOWER(f.intituleCourt) LIKE LOWER(:search)')
            ->andWhere('f.actif = :actif')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('actif', true)
            ->orderBy('f.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formations avec leurs relations (pour éviter les requêtes N+1)
     * 
     * @return Formation[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.niveauQualification', 'nq')
            ->addSelect('nq')
            ->leftJoin('f.typeCertification', 'tc')
            ->addSelect('tc')
            ->leftJoin('f.codesNsf', 'nsf')
            ->addSelect('nsf')
            ->leftJoin('f.codesRome', 'rome')
            ->addSelect('rome')
            ->andWhere('f.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('f.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formations dont l'enregistrement RNCP expire bientôt
     * 
     * @return Formation[]
     */
    public function findExpirantDans(int $mois = 6): array
    {
        $dateLimite = new \DateTime();
        $dateLimite->modify("+{$mois} months");

        return $this->createQueryBuilder('f')
            ->andWhere('f.dateEcheanceRncp IS NOT NULL')
            ->andWhere('f.dateEcheanceRncp <= :limite')
            ->andWhere('f.dateEcheanceRncp >= :aujourdhui')
            ->andWhere('f.actif = :actif')
            ->setParameter('limite', $dateLimite)
            ->setParameter('aujourdhui', new \DateTime())
            ->setParameter('actif', true)
            ->orderBy('f.dateEcheanceRncp', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
