<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;

class CheckoutRepository extends EntityRepository
{
    use WorkflowQueryTrait;

    /**
     * This method is returning the count of all line items,
     * whether originated from a quote, or a shopping list,
     * per Checkout.
     *
     * @param array $checkoutIds
     * @return array
     */
    public function countItemsPerCheckout(array $checkoutIds)
    {
        $databaseResults = $this->createQueryBuilder('c')
            ->select('c.id as id')
            ->addSelect('COALESCE(count(l.id) + count(qp.id), 0) as itemsCount')
            ->leftJoin('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->leftJoin('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')
            ->leftJoin('Oro\Bundle\ShoppingListBundle\Entity\LineItem', 'l', 'WITH', 'l.shoppingList = sl')
            ->leftJoin('Oro\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->leftJoin('Oro\Bundle\SaleBundle\Entity\QuoteProduct', 'qp', 'WITH', 'qp.quote = qd.quote')
            ->groupBy('c.id')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getScalarResult();

        return $this->extractCheckoutItemsCounts($databaseResults);
    }

    /**
     * Returning the source information of the checkouts.
     *
     * @param array $checkoutIds
     * @return array ['<id>' => '<CheckoutSourceEntityInterface>', ...]
     */
    public function getSourcePerCheckout(array $checkoutIds)
    {
        /* @var $checkouts Checkout[] */
        $checkouts = $this->createQueryBuilder('c')
            ->select('c, s, sl, qd')
            ->innerJoin('c.source', 's')
            ->leftJoin('s.shoppingList', 'sl')
            ->leftJoin('s.quoteDemand', 'qd')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getResult();

        $sources = [];
        foreach ($checkouts as $checkout) {
            $sources[$checkout->getId()] = $checkout->getSource()->getEntity();
        }

        return array_filter($sources);
    }

    /**
     * Cutting out ID and ITEMSCOUNT columns from the query
     * and making an associative array out of it.
     *
     * @param $results
     * @return array
     */
    private function extractCheckoutItemsCounts($results)
    {
        $result = [];

        if (!count($results)) {
            return $result;
        }

        $ids        = array_column($results, 'id');
        $itemCounts = array_column($results, 'itemsCount');

        $result = array_combine(
            $ids,
            $itemCounts
        );

        return $result;
    }

    /**
     * @param Quote $quote
     * @param CustomerUser $customerUser
     * @return Checkout
     */
    public function getCheckoutByQuote(Quote $quote, CustomerUser $customerUser)
    {
        $qb = $this->createQueryBuilder('checkout');

        return $qb->addSelect(['source', 'qd', 'quote'])
            ->innerJoin('checkout.source', 'source')
            ->innerJoin('source.quoteDemand', 'qd')
            ->innerJoin('qd.quote', 'quote')
            ->where(
                $qb->expr()->eq('quote', ':quote'),
                $qb->expr()->eq('qd.customerUser', ':customerUser'),
                $qb->expr()->eq('source.deleted', ':deleted'),
                $qb->expr()->eq('checkout.deleted', ':deleted')
            )
            ->setParameter('quote', $quote)
            ->setParameter('customerUser', $customerUser)
            ->setParameter('deleted', false, Type::BOOLEAN)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param CustomerUser $customerUser
     * @param array $sourceCriteria [shoppingList => ShoppingList, deleted => false]
     * @return array
     */
    public function findCheckoutByCustomerUserAndSourceCriteria(CustomerUser $customerUser, array $sourceCriteria)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->innerJoin('c.source', 's')
            ->where(
                $qb->expr()->eq('c.customerUser', ':customerUser'),
                $qb->expr()->eq('c.deleted', ':deleted'),
                $qb->expr()->eq('s.deleted', ':deleted')
            )
            ->setParameter('customerUser', $customerUser)
            ->setParameter('deleted', false, Type::BOOLEAN);

        foreach ($sourceCriteria as $field => $value) {
            $qb->andWhere($qb->expr()->eq('s.' . $field, ':' . $field))
                ->setParameter($field, $value);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function deleteWithoutWorkflowItem()
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
}
