<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Recherche les utilisateurs avec filtres optionnels
     * 
     * @param int|null $roleId     Filtrer par rôle (ID)
     * @param bool|null $actif     Filtrer par statut actif/inactif (null = tous)
     * @param string|null $recherche Recherche textuelle (nom, prénom, email)
     * @return User[]
     */
    public function findWithFilters(?int $roleId = null, ?bool $actif = null, ?string $recherche = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');
        
        // Filtre par rôle
        if ($roleId !== null) {
            $qb->innerJoin('u.rolesEntities', 'r')
               ->andWhere('r.id = :roleId')
               ->setParameter('roleId', $roleId);
        }
        
        // Filtre par statut
        if ($actif !== null) {
            $qb->andWhere('u.actif = :actif')
               ->setParameter('actif', $actif);
        }
        
        // Recherche textuelle
        if ($recherche !== null && trim($recherche) !== '') {
            $recherche = trim($recherche);
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.nom)', ':recherche'),
                    $qb->expr()->like('LOWER(u.prenom)', ':recherche'),
                    $qb->expr()->like('LOWER(u.email)', ':recherche'),
                    // Recherche nom + prénom combinés
                    $qb->expr()->like('LOWER(CONCAT(u.prenom, \' \', u.nom))', ':recherche'),
                    $qb->expr()->like('LOWER(CONCAT(u.nom, \' \', u.prenom))', ':recherche')
                )
            )->setParameter('recherche', '%' . strtolower($recherche) . '%');
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre d'utilisateurs par rôle
     * 
     * @return array<string, int> [code_role => nombre]
     */
    public function countByRole(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('r.code, r.libelle, COUNT(u.id) as total')
            ->innerJoin('u.rolesEntities', 'r')
            ->groupBy('r.id')
            ->orderBy('r.libelle', 'ASC')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['code']] = [
                'libelle' => $row['libelle'],
                'total' => (int) $row['total'],
            ];
        }
        
        return $counts;
    }

    /**
     * Récupère les utilisateurs ayant un rôle spécifique
     * 
     * @return User[]
     */
    public function findByRoleCode(string $roleCode): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.rolesEntities', 'r')
            ->andWhere('r.code = :code')
            ->andWhere('u.actif = true')
            ->setParameter('code', $roleCode)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche pour autocomplétion (retourne nom complet + email)
     * 
     * @return array<array{id: int, text: string, email: string}>
     */
    public function searchForAutocomplete(string $term, int $limit = 10): array
    {
        $users = $this->createQueryBuilder('u')
            ->andWhere('u.actif = true')
            ->andWhere(
                'LOWER(u.nom) LIKE :term OR LOWER(u.prenom) LIKE :term OR LOWER(u.email) LIKE :term'
            )
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->setMaxResults($limit)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
        
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'text' => $user->getNomComplet(),
                'email' => $user->getEmail(),
            ];
        }
        
        return $results;
    }
}
