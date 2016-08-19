<?php

namespace OroB2B\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class PaymentTransactionRepository extends EntityRepository
{
    /**
     * @param string $entityClass
     * @param array $entityIds
     * @return array
     */
    public function getPaymentMethods($entityClass, array $entityIds)
    {
        $queryBuilder = $this->createQueryBuilder('transaction');
        $methods = $queryBuilder
            ->select('transaction.entityIdentifier', 'transaction.paymentMethod')
            ->where($queryBuilder->expr()->eq('transaction.entityClass', ':entityClass'))
            ->setParameter('entityClass', $entityClass)
            ->andWhere($queryBuilder->expr()->in('transaction.entityIdentifier', ':entityIds'))
            ->setParameter('entityIds', $entityIds)
            ->groupBy('transaction.entityIdentifier', 'transaction.paymentMethod')
            ->getQuery()
            ->getResult();

        $groupedResult = [];
        foreach ($methods as $method) {
            $groupedResult[$method['entityIdentifier']][] = $method['paymentMethod'];
        }

        return $groupedResult;
    }
}
