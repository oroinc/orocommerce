<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Handles logic for fetching checkout and checkout items by ids and different criteria
 */
class CheckoutRepository extends ServiceEntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use WorkflowQueryTrait;
    use ResetCustomerUserTrait;

    public function getCheckoutWithRelations(int $checkoutId): ?Checkout
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c', 'cli', 'p', 'kitItemLineItem', 'kitItemLineItemProduct')
            ->leftJoin('c.lineItems', 'cli')
            ->leftJoin('cli.kitItemLineItems', 'kitItemLineItem')
            ->leftJoin('kitItemLineItem.product', 'kitItemLineItemProduct')
            ->leftJoin('cli.product', 'p')
            ->where($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', $checkoutId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Gets the count of line items per Checkout.
     *
     * @param int[] $checkoutIds
     *
     * @return array [checkout id => item count, ...]
     */
    public function countItemsPerCheckout(array $checkoutIds): array
    {
        if (!$checkoutIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('c')
            ->select('c.id AS id, COUNT(cli.id) AS itemsCount')
            ->leftJoin('c.lineItems', 'cli')
            ->groupBy('c.id')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getScalarResult();

        if (!$rows) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['itemsCount'];
        }

        return $result;
    }

    /**
     * Gets the list of checkouts by IDs.
     *
     * @param int[] $checkoutIds
     *
     * @return Checkout[] [checkout id => checkout, ...]
     */
    public function getCheckoutsByIds(array $checkoutIds): array
    {
        if (!$checkoutIds) {
            return [];
        }

        /* @var Checkout[] $checkouts */
        $checkouts = $this->createQueryBuilder('c')
            ->select('c, s')
            ->leftJoin('c.source', 's')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($checkouts as $checkout) {
            $result[$checkout->getId()] = $checkout;
        }

        return $result;
    }

    /**
     * @param CustomerUser $customerUser
     * @param array $sourceCriteria [field name => value, ...]
     * @param string|null $workflowName
     * @param string|null $currency
     *
     * @return Checkout|null
     */
    public function findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
        CustomerUser $customerUser,
        array $sourceCriteria,
        ?string $workflowName,
        ?string $currency = null
    ): ?Checkout {
        $qb = $this->getCheckoutBySourceCriteriaQueryBuilder($sourceCriteria, $workflowName, $currency);
        $qb
            ->select('c.id')
            ->andWhere(
                $qb->expr()->eq('c.customerUser', ':customerUser')
            )
            ->setParameter('customerUser', $customerUser);

        $checkoutId = $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return $checkoutId ? $this->getCheckoutWithRelations($checkoutId) : null;
    }

    public function deleteWithoutWorkflowItem(): void
    {
        $qb = $this->joinWorkflowItem($this->createQueryBuilder('checkout'), 'wi');
        $checkouts = $qb->select('checkout.id AS checkoutId, checkoutSource.id AS checkoutSourceId')
            ->join('checkout.source', 'checkoutSource')
            ->where($qb->expr()->eq('checkout.deleted', ':deleted'), $qb->expr()->isNull('wi.id'))
            ->setParameter('deleted', false)
            ->getQuery()
            ->getResult();
        if (!$checkouts) {
            return;
        }

        $qb = $this->createQueryBuilder('checkout');
        $qb->update()
            ->set('checkout.deleted', ':deleted')
            ->where($qb->expr()->in('checkout.id', ':checkouts'))
            ->setParameter('deleted', true)
            ->setParameter('checkouts', array_column($checkouts, 'checkoutId'))
            ->getQuery()
            ->execute();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(CheckoutSource::class, 'checkoutSource')
            ->set('checkoutSource.deleted', ':deleted')
            ->where($qb->expr()->in('checkoutSource.id', ':checkoutSources'))
            ->setParameter('deleted', true)
            ->setParameter('checkoutSources', array_column($checkouts, 'checkoutSourceId'))
            ->getQuery()
            ->execute();
    }

    /**
     * @param string $paymentMethod
     *
     * @return Checkout[]
     */
    public function findByPaymentMethod(string $paymentMethod): array
    {
        return $this->findBy(['paymentMethod' => $paymentMethod]);
    }

    /**
     * @param array $sourceCriteria [field name => value, ...]
     * @param string|null $workflowName
     * @param string|null $currency
     *
     * @return Checkout|null
     */
    public function findCheckoutBySourceCriteriaWithCurrency(
        array $sourceCriteria,
        ?string $workflowName,
        ?string $currency = null
    ): ?Checkout {
        $qb = $this->getCheckoutBySourceCriteriaQueryBuilder($sourceCriteria, $workflowName, $currency);
        $qb->select('c.id');

        $checkoutId = $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return $checkoutId ? $this->getCheckoutWithRelations($checkoutId) : null;
    }

    /**
     * @return \Iterator<int, Checkout>
     */
    public function findWithInvalidSubtotals(): \Iterator
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c, cs')
            ->join('c.subtotals', 'cs')
            ->where('cs.valid = :valid')
            ->setParameter('valid', false, Types::BOOLEAN);

        return new BufferedIdentityQueryResultIterator($qb);
    }

    public function findDuplicateCheckouts(
        CustomerUser $customerUser,
        array $sourceCriteria,
        string $workflowName,
        array $excludedIds,
        ?string $currency = null
    ): array {
        $qb = $this->getCheckoutBySourceCriteriaQueryBuilder(
            $sourceCriteria,
            $workflowName,
            $currency
        );

        return $qb->andWhere($qb->expr()->eq('c.customerUser', ':customerUser'))
            ->andWhere($qb->expr()->notIn('c.id', ':excludedIds'))
            ->setParameter('customerUser', $customerUser)
            ->setParameter('excludedIds', $excludedIds)
            ->getQuery()
            ->getResult();
    }

    private function getCheckoutBySourceCriteriaQueryBuilder(
        array $sourceCriteria,
        ?string $workflowName = null,
        ?string $currency = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('c');
        $this->joinWorkflowItem($qb)
            ->innerJoin('c.source', 's')
            ->where(
                $qb->expr()->eq('c.deleted', ':deleted'),
                $qb->expr()->eq('s.deleted', ':deleted'),
                $qb->expr()->eq('c.completed', ':completed')
            )
            ->setParameter('deleted', false, Types::BOOLEAN)
            ->setParameter('completed', false, Types::BOOLEAN);

        if ($workflowName) {
            $qb
                ->andWhere($qb->expr()->eq('workflowItem.workflowName', ':workflowName'))
                ->setParameter('workflowName', $workflowName);
        } else {
            $qb->andWhere($qb->expr()->isNull('workflowItem.id'));
        }

        if ($currency) {
            $qb->andWhere($qb->expr()->eq('c.currency', ':currency'))
                ->setParameter('currency', $currency, Types::STRING);
        }

        foreach ($sourceCriteria as $field => $value) {
            QueryBuilderUtil::checkIdentifier($field);
            $qb->andWhere($qb->expr()->eq('s.' . $field, ':' . $field))
                ->setParameter($field, $value);
        }

        return $qb;
    }
}
