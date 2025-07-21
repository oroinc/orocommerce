<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Doctrine repository for PaymentTransaction entity
 */
class PaymentTransactionRepository extends ServiceEntityRepository
{
    /**
     * @param string $entityClass
     * @param array<int> $entityIds
     *
     * @return array<int,array<string>>
     *     [
     *          42 => [ // Source entity ID
     *              // List of payment method identifiers.
     *              'payment_term_11',
     *              'money_order_20',
     *          ],
     *     ]
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
     * @param string $action
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
                'successful' => true,
            ]
        );
    }

    public function hasSuccessfulRelatedTransactionsByAction(PaymentTransaction $transaction, string $action): bool
    {
        return 0 < $this->count([
                'sourcePaymentTransaction' => $transaction,
                'action' => $action,
                'successful' => true,
            ]);
    }
}
