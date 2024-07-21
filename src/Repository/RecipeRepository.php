<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 *
 * @method Recipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recipe[]    findAll()
 * @method Recipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function findRecipeDurationLowerThan(int $duration) : array {
    
        return $this->createQueryBuilder('r')
        ->where('r.duration <= :duration')
        ->orderBy('r.duration', 'ASC')
        ->setParameter('duration', $duration)
        ->getQuery()
        ->getResult();
    }

    public function findByName(string $name)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }

    public function findByQuery(string $query): array
    {
        $qb = $this->createQueryBuilder('r');
        return $qb->where($qb->expr()->like('r.title', ':query'))
            // ->orWhere($qb->expr()->like('r.content', ':query'))
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
    
    //    /**
    //     * @return Recipe[] Returns an array of Recipe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Recipe
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAllSorted($sort)
    {
        $qb = $this->createQueryBuilder('r');

        switch ($sort) {
            case 'alphabetical':
                $qb->orderBy('r.title', 'ASC');
                break;
            case 'duration':
                $qb->orderBy('r.duration', 'ASC');
                break;
            case 'last_updated':
                $qb->orderBy('r.updatedAt', 'DESC');
                break;
            default:
                $qb->orderBy('r.createdAt', 'ASC');
                break;
        }

        return $qb->getQuery()->getResult();
    }
}
