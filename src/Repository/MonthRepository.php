<?php

namespace App\Repository;

use App\Entity\Month;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Month>
 */
class MonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Month::class);
    }

    /**
    * @return Month[] Returns an array of Advice objects
    */
    public function findByMonthNumber($value): ?array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.number AS monthNumber, a.id AS adviceId, a.content AS adviceContent')
            ->join('m.advices', 'a')
            ->where('m.number = :number')
            ->setParameter('number', $value)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            return null; // Si aucun mois trouvé, retourner null
        }
        
        // Structurer le résultat
        $advices = [];
        foreach ($result as $row) {
            if (!empty($row['adviceId'])) {
                $advices[] = [
                    'id' => $row['adviceId'],
                    'content' => $row['adviceContent'],
                ];
            }
        }
    
        return [
            'number' => $result[0]['monthNumber'],
            'advices' => $advices,
        ];
    }

       /**
        * @return ?Month Returns object month
        */
        public function findIdBy($value):?Month
        {
            return $this->createQueryBuilder('n')
            ->where('n.number = :number')
            ->setParameter('number', $value)
            ->getQuery()
            ->getOneOrNullResult();
        }
    //    /**
    //     * @return Month[] Returns an array of Month objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }
    


    //    public function findOneBySomeField($value): ?Month
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
