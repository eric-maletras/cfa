<?php

namespace App\Repository;

use App\Entity\CodeROME;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CodeROME>
 */
class CodeROMERepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodeROME::class);
    }

    /**
     * Récupère tous les codes actifs ordonnés par code
     * 
     * @return CodeROME[]
     */
    public function findAllActifs(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('r.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un code par sa valeur
     */
    public function findByCode(string $code): ?CodeROME
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les codes d'un domaine (lettre)
     * 
     * @return CodeROME[]
     */
    public function findByDomaine(string $domaineCode): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.domaineCode = :domaine')
            ->andWhere('r.actif = :actif')
            ->setParameter('domaine', strtoupper($domaineCode))
            ->setParameter('actif', true)
            ->orderBy('r.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la liste des domaines distincts
     * 
     * @return array<array{domaineCode: string, domaineLibelle: string}>
     */
    public function findDomaines(): array
    {
        return $this->createQueryBuilder('r')
            ->select('DISTINCT r.domaineCode, r.domaineLibelle')
            ->andWhere('r.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('r.domaineCode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par libellé (recherche partielle)
     * 
     * @return CodeROME[]
     */
    public function searchByLibelle(string $search): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('LOWER(r.libelle) LIKE LOWER(:search)')
            ->andWhere('r.actif = :actif')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('actif', true)
            ->orderBy('r.code', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par code ou libellé
     * 
     * @return CodeROME[]
     */
    public function search(string $search): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.code LIKE :search OR LOWER(r.libelle) LIKE LOWER(:searchLib)')
            ->andWhere('r.actif = :actif')
            ->setParameter('search', strtoupper($search) . '%')
            ->setParameter('searchLib', '%' . $search . '%')
            ->setParameter('actif', true)
            ->orderBy('r.code', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les codes pour un affichage dans un formulaire
     * 
     * @return array<string, int>
     */
    public function findForSelect(): array
    {
        $codes = $this->findAllActifs();
        $result = [];
        
        foreach ($codes as $code) {
            $result[$code->getCode() . ' - ' . $code->getLibelle()] = $code->getId();
        }
        
        return $result;
    }

    /**
     * Récupère les codes pour un affichage groupé par domaine
     * 
     * @return array<string, array<string, int>>
     */
    public function findForSelectGroupedByDomaine(): array
    {
        $codes = $this->findAllActifs();
        $result = [];
        
        foreach ($codes as $code) {
            $domaine = $code->getDomaineCode() . ' - ' . $code->getDomaineLibelle();
            if (!isset($result[$domaine])) {
                $result[$domaine] = [];
            }
            $result[$domaine][$code->getCode() . ' - ' . $code->getLibelle()] = $code->getId();
        }
        
        return $result;
    }

    /**
     * Récupère les codes ROME relatifs à l'informatique (domaine M - sous-domaine 18)
     * 
     * @return CodeROME[]
     */
    public function findCodesInformatique(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.code LIKE :pattern')
            ->andWhere('r.actif = :actif')
            ->setParameter('pattern', 'M18%')
            ->setParameter('actif', true)
            ->orderBy('r.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les codes ROME relatifs à la maintenance informatique (domaine I)
     * 
     * @return CodeROME[]
     */
    public function findCodesMaintenanceInformatique(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.code LIKE :pattern')
            ->andWhere('r.actif = :actif')
            ->setParameter('pattern', 'I14%')
            ->setParameter('actif', true)
            ->orderBy('r.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de codes par domaine
     * 
     * @return array<array{domaineCode: string, domaineLibelle: string, count: int}>
     */
    public function countByDomaine(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.domaineCode, r.domaineLibelle, COUNT(r.id) as count')
            ->andWhere('r.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('r.domaineCode, r.domaineLibelle')
            ->orderBy('r.domaineCode', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
