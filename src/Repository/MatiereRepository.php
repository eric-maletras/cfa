<?php

namespace App\Repository;

use App\Entity\Matiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Matiere
 *
 * @extends ServiceEntityRepository<Matiere>
 */
class MatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Matiere::class);
    }

    /**
     * Retourne toutes les matières actives, triées par code
     *
     * @return Matiere[]
     */
    public function findAllActives(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les matières avec le comptage des formations
     *
     * @return array
     */
    public function findAllWithFormationCount(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m', 'COUNT(fm.id) as formationCount')
            ->leftJoin('m.formationMatieres', 'fm')
            ->groupBy('m.id')
            ->orderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par code ou libellé
     *
     * @param string $search
     * @return Matiere[]
     */
    public function search(string $search): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.code LIKE :search OR m.libelle LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une matière par son code
     *
     * @param string $code
     * @return Matiere|null
     */
    public function findByCode(string $code): ?Matiere
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les matières non utilisées par une formation donnée
     *
     * @param int $formationId
     * @return Matiere[]
     */
    public function findNotInFormation(int $formationId): array
    {
        $qb = $this->createQueryBuilder('m');
        
        // Sous-requête pour les matières déjà dans la formation
        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('IDENTITY(fm.matiere)')
            ->from('App\Entity\FormationMatiere', 'fm')
            ->where('fm.formation = :formationId');

        return $qb
            ->andWhere($qb->expr()->notIn('m.id', $subQb->getDQL()))
            ->andWhere('m.actif = :actif')
            ->setParameter('formationId', $formationId)
            ->setParameter('actif', true)
            ->orderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une matière est utilisée par au moins une formation
     *
     * @param Matiere $matiere
     * @return bool
     */
    public function isUsedByFormations(Matiere $matiere): bool
    {
        $count = $this->createQueryBuilder('m')
            ->select('COUNT(fm.id)')
            ->leftJoin('m.formationMatieres', 'fm')
            ->andWhere('m.id = :id')
            ->setParameter('id', $matiere->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
