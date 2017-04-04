<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentTransactionRepository extends EntityRepository
{
    /**
     * @param string $entityClass
     * @param array  $entityIds
     *
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

    /**
     * @param $paymentMethod
     *
     * @return PaymentTransaction[]
     */
    public function findByPaymentMethod($paymentMethod)
    {
        return $this->findBy(['paymentMethod' => $paymentMethod]);
    }

    /**
     * @param PaymentTransaction $transaction
     * @param string             $action
     *
     * @return PaymentTransaction[]
     */
    public function findSuccessfulRelatedTransactionsByAction(
        PaymentTransaction $transaction,
        $action
    ) {
        return $this->findBy(
            [
                'sourcePaymentTransaction' => $transaction,
                'action' => $action,
                'successful' => true
            ]
        );
    }
}
