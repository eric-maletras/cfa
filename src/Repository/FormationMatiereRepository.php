<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\FormationMatiere;
use App\Entity\Matiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité FormationMatiere
 *
 * @extends ServiceEntityRepository<FormationMatiere>
 */
class FormationMatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationMatiere::class);
    }

    /**
     * Retourne les matières d'une formation, triées par ordre
     *
     * @param Formation $formation
     * @return FormationMatiere[]
     */
    public function findByFormation(Formation $formation): array
    {
        return $this->createQueryBuilder('fm')
            ->andWhere('fm.formation = :formation')
            ->setParameter('formation', $formation)
            ->leftJoin('fm.matiere', 'm')
            ->addSelect('m')
            ->orderBy('fm.ordre', 'ASC')
            ->addOrderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les formations utilisant une matière
     *
     * @param Matiere $matiere
     * @return FormationMatiere[]
     */
    public function findByMatiere(Matiere $matiere): array
    {
        return $this->createQueryBuilder('fm')
            ->andWhere('fm.matiere = :matiere')
            ->setParameter('matiere', $matiere)
            ->leftJoin('fm.formation', 'f')
            ->addSelect('f')
            ->orderBy('f.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une liaison formation-matière existe déjà
     *
     * @param Formation $formation
     * @param Matiere $matiere
     * @return FormationMatiere|null
     */
    public function findByFormationAndMatiere(Formation $formation, Matiere $matiere): ?FormationMatiere
    {
        return $this->createQueryBuilder('fm')
            ->andWhere('fm.formation = :formation')
            ->andWhere('fm.matiere = :matiere')
            ->setParameter('formation', $formation)
            ->setParameter('matiere', $matiere)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne le prochain ordre disponible pour une formation
     *
     * @param Formation $formation
     * @return int
     */
    public function getNextOrdre(Formation $formation): int
    {
        $maxOrdre = $this->createQueryBuilder('fm')
            ->select('MAX(fm.ordre)')
            ->andWhere('fm.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxOrdre ?? -1) + 1;
    }

    /**
     * Calcule le volume horaire total d'une formation
     *
     * @param Formation $formation
     * @return int
     */
    public function getTotalVolumeHeures(Formation $formation): int
    {
        $total = $this->createQueryBuilder('fm')
            ->select('SUM(fm.volumeHeuresReferentiel)')
            ->andWhere('fm.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($total ?? 0);
    }

    /**
     * Retourne les statistiques des matières pour une formation
     *
     * @param Formation $formation
     * @return array
     */
    public function getStatistiquesFormation(Formation $formation): array
    {
        $result = $this->createQueryBuilder('fm')
            ->select('COUNT(fm.id) as nbMatieres')
            ->addSelect('SUM(fm.volumeHeuresReferentiel) as totalHeures')
            ->addSelect('AVG(fm.coefficient) as moyenneCoef')
            ->andWhere('fm.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleResult();

        return [
            'nbMatieres' => (int) $result['nbMatieres'],
            'totalHeures' => (int) ($result['totalHeures'] ?? 0),
            'moyenneCoef' => $result['moyenneCoef'] ? round((float) $result['moyenneCoef'], 2) : null,
        ];
    }
}
