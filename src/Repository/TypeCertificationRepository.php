<?php

namespace App\Repository;

use App\Entity\TypeCertification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeCertification>
 */
class TypeCertificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeCertification::class);
    }

    /**
     * Récupère tous les types actifs ordonnés par ordre d'affichage puis libellé
     * 
     * @return TypeCertification[]
     */
    public function findAllActifs(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('t.ordreAffichage', 'ASC')
            ->addOrderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un type par son code
     */
    public function findByCode(string $code): ?TypeCertification
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les types pour un affichage dans un formulaire
     * 
     * @return array<string, int>
     */
    public function findForSelect(): array
    {
        $types = $this->findAllActifs();
        $result = [];
        
        foreach ($types as $type) {
            $label = $type->getLibelleAbrege() 
                ? $type->getLibelleAbrege() . ' - ' . $type->getLibelle()
                : $type->getLibelle();
            $result[$label] = $type->getId();
        }
        
        return $result;
    }

    /**
     * Récupère les types par certificateur
     * 
     * @return TypeCertification[]
     */
    public function findByCertificateurType(string $certificateurType): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.certificateurType = :type')
            ->andWhere('t.actif = :actif')
            ->setParameter('type', $certificateurType)
            ->setParameter('actif', true)
            ->orderBy('t.ordreAffichage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les types éligibles à l'apprentissage
     * 
     * @return TypeCertification[]
     */
    public function findEligiblesApprentissage(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.apprentissagePossible = :oui')
            ->andWhere('t.actif = :actif')
            ->setParameter('oui', true)
            ->setParameter('actif', true)
            ->orderBy('t.ordreAffichage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les types enregistrés de droit au RNCP
     * 
     * @return TypeCertification[]
     */
    public function findEnregistresDeDroit(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.enregistrementRncp = :mode')
            ->andWhere('t.actif = :actif')
            ->setParameter('mode', 'de_droit')
            ->setParameter('actif', true)
            ->orderBy('t.ordreAffichage', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
