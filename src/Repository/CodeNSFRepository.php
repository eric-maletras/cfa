<?php

namespace App\Repository;

use App\Entity\CodeNSF;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CodeNSF>
 */
class CodeNSFRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodeNSF::class);
    }

    /**
     * Récupère tous les codes actifs d'un niveau donné
     * 
     * @return CodeNSF[]
     */
    public function findByNiveau(int $niveau): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.niveau = :niveau')
            ->andWhere('n.actif = :actif')
            ->setParameter('niveau', $niveau)
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les domaines de niveau 1 (racines)
     * 
     * @return CodeNSF[]
     */
    public function findDomaines(): array
    {
        return $this->findByNiveau(1);
    }

    /**
     * Récupère les groupes de spécialités (niveau 3) - utilisés pour les formations
     * 
     * @return CodeNSF[]
     */
    public function findGroupesSpecialites(): array
    {
        return $this->findByNiveau(3);
    }

    /**
     * Trouve un code par sa valeur
     */
    public function findByCode(string $code): ?CodeNSF
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les enfants directs d'un code
     * 
     * @return CodeNSF[]
     */
    public function findEnfants(CodeNSF $parent): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.parent = :parent')
            ->andWhere('n.actif = :actif')
            ->setParameter('parent', $parent)
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'arborescence complète pour un affichage hiérarchique
     * 
     * @return CodeNSF[]
     */
    public function findArborescence(): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.parent', 'p')
            ->addSelect('p')
            ->andWhere('n.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par libellé (recherche partielle)
     * 
     * @return CodeNSF[]
     */
    public function searchByLibelle(string $search): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('LOWER(n.libelle) LIKE LOWER(:search)')
            ->andWhere('n.actif = :actif')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les codes pour un affichage dans un formulaire (niveau 3 généralement)
     * Format hiérarchique avec indentation
     * 
     * @return array<string, int>
     */
    public function findForSelect(int $niveau = 3): array
    {
        $codes = $this->findByNiveau($niveau);
        $result = [];
        
        foreach ($codes as $code) {
            $result[$code->getCode() . ' - ' . $code->getLibelle()] = $code->getId();
        }
        
        return $result;
    }

    /**
     * Récupère les codes NSF relatifs à l'informatique
     * 
     * @return CodeNSF[]
     */
    public function findCodesInformatique(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.code LIKE :code326')
            ->andWhere('n.actif = :actif')
            ->setParameter('code326', '326%')
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les codes d'un type de domaine
     * 
     * @return CodeNSF[]
     */
    public function findByTypeDomaine(string $typeDomaine): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.typeDomaine = :type')
            ->andWhere('n.actif = :actif')
            ->setParameter('type', $typeDomaine)
            ->setParameter('actif', true)
            ->orderBy('n.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
