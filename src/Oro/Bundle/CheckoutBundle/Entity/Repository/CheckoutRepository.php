<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Handles logic for fetching checkout and checkout items by ids and different criteria
 */
class CheckoutRepository extends EntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use WorkflowQueryTrait;
    use ResetCustomerUserTrait;

    /**
     * Used in CheckoutController::checkoutAction().
     * Loads related entities to eliminate extra queries on checkout.
     */
    public function findForCheckoutAction(int $checkoutId): ?Checkout
    {
        $qb = $this->createQueryBuilder('checkout');

        /** @var Checkout $checkout */
        $checkout = $qb
            ->select(
                'checkout',
                'line_item',
                'product',
                'product_manage_inventory',
                'product_minimum_quantity',
                'product_maximum_quantity',
                'product_highlight_low_inventory',
                'product_is_upcoming',
                'category',
                'category_manage_inventory',
                'category_minimum_quantity',
                'category_maximum_quantity',
                'category_highlight_low_inventory',
                'category_is_upcoming'
            )
            ->leftJoin('checkout.lineItems', 'line_item')
            ->leftJoin('line_item.product', 'product')
            ->leftJoin('product.manageInventory', 'product_manage_inventory')
            ->leftJoin('product.highlightLowInventory', 'product_highlight_low_inventory')
            ->leftJoin('product.isUpcoming', 'product_is_upcoming')
            ->leftJoin('product.minimumQuantityToOrder', 'product_minimum_quantity')
            ->leftJoin('product.maximumQuantityToOrder', 'product_maximum_quantity')
            ->leftJoin('product.category', 'category')
            ->leftJoin('category.manageInventory', 'category_manage_inventory')
            ->leftJoin('category.highlightLowInventory', 'category_highlight_low_inventory')
            ->leftJoin('category.isUpcoming', 'category_is_upcoming')
            ->leftJoin('category.minimumQuantityToOrder', 'category_minimum_quantity')
            ->leftJoin('category.maximumQuantityToOrder', 'category_maximum_quantity')
            ->where($qb->expr()->eq('checkout.id', ':id'))
            ->setParameter('id', $checkoutId, Types::INTEGER)
            ->getQuery()->getOneOrNullResult();

        if ($checkout && $checkout->getLineItems()->count()) {
            $lineItems = $checkout->getLineItems();
            $productsIds = [];
            foreach ($lineItems as $lineItem) {
                if ($lineItem->getProduct()) {
                    $productId = $lineItem->getProduct()->getId();
                    $productsIds[$productId] = $productId;
                }
            }

            $this->loadRelatedProductNames($productsIds);
        }

        return $checkout;
    }

    private function loadRelatedProductNames(array $productIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('partial product.{id}', 'product_name')
            ->from(Product::class, 'product')
            ->innerJoin('product.names', 'product_name')
            ->where($qb->expr()->in('product', ':products'))
            ->setParameter('products', $productIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $checkoutId
     *
     * @return Checkout|null
     */
    public function getCheckoutWithRelations($checkoutId)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c', 'cli', 'p')
            ->leftJoin('c.lineItems', 'cli')
            ->leftJoin('cli.product', 'p')
            ->where($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', $checkoutId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Return the count of line items per Checkout.
     *
     * @param array $checkoutIds
     *
     * @return array
     */
    public function countItemsPerCheckout(array $checkoutIds)
    {
        if (0 === count($checkoutIds)) {
            return [];
        }

        $databaseResults = $this->createQueryBuilder('c')
            ->select('c.id as id')
            ->addSelect('count(cli.id) as itemsCount')
            ->leftJoin('c.lineItems', 'cli')
            ->groupBy('c.id')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getScalarResult();

        return $this->extractCheckoutItemsCounts($databaseResults);
    }

    /**
     * Return the list of checkouts by ids.
     *
     * @param array $checkoutIds
     *
     * @return array|Checkout[] ['<id>' => '<Checkout>', ...]
     */
    public function getCheckoutsByIds(array $checkoutIds)
    {
        /* @var $checkouts Checkout[] */
        $checkouts = $this->createQueryBuilder('c')
            ->select('c, s')
            ->leftJoin('c.source', 's')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getResult();

        $sources = [];
        foreach ($checkouts as $checkout) {
            $sources[$checkout->getId()] = $checkout;
        }

        return $sources;
    }

    /**
     * Cutting out ID and ITEMSCOUNT columns from the query
     * and making an associative array out of it.
     *
     * @param $results
     *
     * @return array
     */
    private function extractCheckoutItemsCounts($results)
    {
        $result = [];

        if (!count($results)) {
            return $result;
        }

        $ids = array_column($results, 'id');
        $itemCounts = array_column($results, 'itemsCount');

        $result = array_combine(
            $ids,
            $itemCounts
        );

        return $result;
    }

    /**
     * @param CustomerUser $customerUser
     * @param array        $sourceCriteria [shoppingList => ShoppingList, deleted => false]
     * @param string       $workflowName
     * @param string|null  $currency
     *
     * @return Checkout|null
     */
    public function findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
        CustomerUser $customerUser,
        array $sourceCriteria,
        string $workflowName,
        ?string $currency = null
    ) {
        $qb = $this->getCheckoutBySourceCriteriaQueryBuilder($sourceCriteria, $workflowName, $currency);
        $qb
            ->andWhere(
                $qb->expr()->eq('c.customerUser', ':customerUser')
            )
            ->setParameter('customerUser', $customerUser);

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

    /**
     * @param $paymentMethod
     *
     * @return Checkout[]
     */
    public function findByPaymentMethod($paymentMethod)
    {
        return $this->findBy([
            'paymentMethod' => $paymentMethod
        ]);
    }

    /**
     * @param array  $sourceCriteria [shoppingList => ShoppingList, deleted => false]
     * @param string $workflowName
     * @param string|null $currency
     *
     * @return Checkout|null
     */
    public function findCheckoutBySourceCriteriaWithCurrency(
        array $sourceCriteria,
        string $workflowName,
        ?string $currency = null
    ) {
        $qb = $this->getCheckoutBySourceCriteriaQueryBuilder($sourceCriteria, $workflowName, $currency);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return \Iterator|Checkout[]
     */
    public function findWithInvalidSubtotals()
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c, cs')
            ->join('c.subtotals', 'cs')
            ->where('cs.valid = :valid')
            ->setParameter('valid', false, Types::BOOLEAN);

        return new BufferedIdentityQueryResultIterator($qb);
    }

    /**
     * @param array  $sourceCriteria [shoppingList => ShoppingList, deleted => false]
     * @param string $workflowName
     * @param string|null $currency
     *
     * @return QueryBuilder
     */
    private function getCheckoutBySourceCriteriaQueryBuilder(
        array $sourceCriteria,
        string $workflowName,
        ?string $currency = null
    ) {
        $qb = $this->createQueryBuilder('c');
        $this->joinWorkflowItem($qb)
            ->innerJoin('c.source', 's')
            ->where(
                $qb->expr()->eq('c.deleted', ':deleted'),
                $qb->expr()->eq('s.deleted', ':deleted'),
                $qb->expr()->eq('c.completed', ':completed'),
                $qb->expr()->eq('workflowItem.workflowName', ':workflowName')
            )
            ->setParameter('deleted', false, Types::BOOLEAN)
            ->setParameter('completed', false, Types::BOOLEAN)
            ->setParameter('workflowName', $workflowName);

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
