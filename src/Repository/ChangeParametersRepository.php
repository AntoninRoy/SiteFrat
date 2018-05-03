<?php

namespace App\Repository;

use App\Entity\ChangeParameters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ChangeParameters|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChangeParameters|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChangeParameters[]    findAll()
 * @method ChangeParameters[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChangeParametersRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ChangeParameters::class);
    }

//    /**
//     * @return ChangeParameters[] Returns an array of ChangeParameters objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ChangeParameters
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
