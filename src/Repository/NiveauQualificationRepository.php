<?php

namespace App\Repository;

use App\Entity\NiveauQualification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NiveauQualification>
 */
class NiveauQualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NiveauQualification::class);
    }

    /**
     * Récupère tous les niveaux actifs ordonnés par code
     * 
     * @return NiveauQualification[]
     */
    public function findAllActifs(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un niveau par son code
     */
    public function findByCode(int $code): ?NiveauQualification
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les niveaux pour un affichage dans un formulaire
     * Retourne un tableau associatif [libelle => id]
     * 
     * @return array<string, int>
     */
    public function findForSelect(): array
    {
        $niveaux = $this->findAllActifs();
        $result = [];
        
        foreach ($niveaux as $niveau) {
            $result[$niveau->getLibelle()] = $niveau->getId();
        }
        
        return $result;
    }

    /**
     * Récupère les niveaux correspondant à l'enseignement supérieur (5 à 8)
     * 
     * @return NiveauQualification[]
     */
    public function findNiveauxSuperieurs(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.code >= :minCode')
            ->andWhere('n.actif = :actif')
            ->setParameter('minCode', 5)
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
