<?php

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Repository for Order entity provides methods to extract order related info.
 */
class OrderRepository extends ServiceEntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use ResetCustomerUserTrait;

    private ?AclHelper $aclHelper = null;

    private ?DateHelper $dateHelper = null;

    private const AMOUNT_TYPE_SUBTOTAL_WITH_DISCOUNT = 'subtotal_with_discounts';
    private const AMOUNT_TYPE_SUBTOTAL = 'subtotal';
    private const AMOUNT_TYPE_TOTAL = 'total';

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

    public function setDateHelper(DateHelper $dateHelper): void
    {
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param array $removingCurrencies
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    ) {
        $qb = $this->createQueryBuilder('orders');
        $qb
            ->select('COUNT(orders.id)')
            ->where($qb->expr()->in('orders.currency', ':removingCurrencies'))
            ->setParameter('removingCurrencies', $removingCurrencies);

        if ($organization instanceof Organization) {
            $qb
                ->andWhere(($qb->expr()->in('orders.organization', ':organization')))
                ->setParameter('organization', $organization);
        }

        return (bool)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $id
     * @return Order|null
     */
    public function getOrderWithRelations($id)
    {
        $qb = $this->createQueryBuilder('orders');
        $qb->select('orders, lineItems, shippingAddress, billingAddress, discounts')
            ->leftJoin('orders.lineItems', 'lineItems')
            ->leftJoin('orders.shippingAddress', 'shippingAddress')
            ->leftJoin('orders.billingAddress', 'billingAddress')
            ->leftJoin('orders.discounts', 'discounts')
            ->where($qb->expr()->eq('orders.id', ':orderId'))
            ->setParameter('orderId', $id)
            ->addOrderBy($qb->expr()->asc('orders.id'))
            ->addOrderBy($qb->expr()->asc('lineItems.id'));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $productIds
     * @param int $websiteId
     * @param array $orderStatuses
     *
     * @return QueryBuilder
     */
    public function getLatestOrderedProductsInfo(array $productIds, $websiteId, $orderStatuses)
    {
        $queryBuilder = $this->getBaseLatestOrderedProductsQueryBuilder($websiteId, $orderStatuses);
        $queryBuilder
            ->addSelect('IDENTITY(lineItems.product) as product_id')
            ->andWhere($queryBuilder->expr()->in('lineItems.product', ':productIdList'))
            ->andWhere($queryBuilder->expr()->isNull('lineItems.parentProduct'))
            ->andWhere($queryBuilder->expr()->isNotNull('orders.customerUser'))
            ->addGroupBy('lineItems.product')
            ->orderBy('lineItems.product')
            ->setParameter('productIdList', $productIds);

        return $queryBuilder;
    }

    /**
     * @param array $productIds
     * @param int $websiteId
     * @param array $orderStatuses
     *
     * @return QueryBuilder
     */
    public function getLatestOrderedParentProductsInfo(array $productIds, $websiteId, $orderStatuses)
    {
        $queryBuilder = $this->getBaseLatestOrderedProductsQueryBuilder($websiteId, $orderStatuses);
        $queryBuilder
            ->addSelect('IDENTITY(lineItems.parentProduct) as product_id')
            ->andWhere($queryBuilder->expr()->in('lineItems.parentProduct', ':productIdList'))
            ->andWhere($queryBuilder->expr()->isNotNull('orders.customerUser'))
            ->addGroupBy('lineItems.parentProduct')
            ->orderBy('lineItems.parentProduct')
            ->setParameter('productIdList', $productIds);

        return $queryBuilder;
    }

    private function getBaseLatestOrderedProductsQueryBuilder(int $websiteId, array $orderStatuses): QueryBuilder
    {
        $qb = $this->createQueryBuilder('orders');
        $qb
            ->select('IDENTITY(orders.customerUser) as customer_user_id')
            ->addSelect(
                $qb->expr()->max('orders.createdAt') . ' as created_at'
            )
            ->innerJoin('orders.lineItems', 'lineItems')
            ->andWhere($qb->expr()->eq('orders.website', ':websiteId'))
            ->andWhere($qb->expr()->in('orders.internal_status', ':orderStatuses'))
            ->groupBy('orders.customerUser');

        $qb
            ->setParameter('orderStatuses', $orderStatuses)
            ->setParameter('websiteId', $websiteId);

        return $qb;
    }

    /**
     * @param \DateTime $dateTimeFrom
     * @param \DateTime $dateTimeTo
     * @param array $includedOrderStatuses
     * @param bool $isIncludeSubOrders
     * @param string $scaleType
     *
     * @return array<array{
     *     number: int,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }>
     */
    public function getSalesOrdersNumber(
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): array {
        $qb = $this->getSalesOrdersNumberQueryBuilder(
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        return $this->aclHelper->apply($qb)->getResult();
    }

    public function getSalesOrdersNumberQueryBuilder(
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('COUNT(o.id) AS number');

        return $this->getSalesOrdersDataQueryBuilder(
            $queryBuilder,
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );
    }

    /**
     * @param QueryBuilder $queryBuilder Query builder with selection expressions
     * @param \DateTime $dateTimeFrom
     * @param \DateTime $dateTimeTo
     * @param array $includedOrderStatuses
     * @param bool $isIncludeSubOrders
     * @param string $scaleType
     *
     * @return array<array{
     *     value: string,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }>
     */
    public function getSalesOrdersData(
        QueryBuilder $queryBuilder,
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): array {
        $queryBuilder = $this->getSalesOrdersDataQueryBuilder(
            $queryBuilder,
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    public function getSalesOrdersDataQueryBuilder(
        QueryBuilder $queryBuilder,
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): QueryBuilder {
        $dateTimeFrom = clone $dateTimeFrom;
        $dateTimeTo = clone $dateTimeTo;

        if (count($includedOrderStatuses)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('o.internal_status', ':includedOrderStatuses'))
                ->setParameter('includedOrderStatuses', $includedOrderStatuses);
        }

        if ($isIncludeSubOrders === false) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNull('o.parent'));
        }

        $this->dateHelper->addDatePartsSelect($dateTimeFrom, $dateTimeTo, $queryBuilder, 'o.createdAt', $scaleType);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->between('o.createdAt', ':from', ':to'))
            ->setParameter('to', $dateTimeTo, Types::DATETIME_MUTABLE)
            ->setParameter('from', $dateTimeFrom, Types::DATETIME_MUTABLE);

        return $queryBuilder;
    }

    /**
     * @param \DateTime $dateTimeFrom
     * @param \DateTime $dateTimeTo
     * @param array $includedOrderStatuses
     * @param bool $isIncludeSubOrders
     * @param string $amountType
     * @param string $scaleType
     *
     * @return array<array{
     *     amount: string,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }>
     */
    public function getSalesOrdersVolume(
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        string $scaleType
    ): array {
        $queryBuilder = $this->getSalesOrdersVolumeQueryBuilder(
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $amountType,
            null,
            $scaleType
        );

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param \DateTime $dateTimeFrom
     * @param \DateTime $dateTimeTo
     * @param array $includedOrderStatuses
     * @param bool $isIncludeSubOrders
     * @param string $amountType
     * @param string $currency
     * @param string $scaleType
     *
     * @return array<array{
     *     amount: string,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }>
     */
    public function getSalesOrdersVolumeForCurrency(
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        string $currency,
        string $scaleType
    ): array {
        $queryBuilder = $this->getSalesOrdersVolumeQueryBuilder(
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $amountType,
            $currency,
            $scaleType
        );

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    public function getSalesOrdersVolumeQueryBuilder(
        \DateTime $dateTimeFrom,
        \DateTime $dateTimeTo,
        array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        ?string $currency,
        string $scaleType
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o');

        $amountField = $this->getAmountField($amountType, $currency);
        $qb->select(QueryBuilderUtil::sprintf(
            'SUM(CASE WHEN %s IS NOT NULL THEN %s ELSE 0 END) AS amount',
            $amountField,
            $amountField
        ));

        $salesOrdersDataQueryBuilder = $this->getSalesOrdersDataQueryBuilder(
            $qb,
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        if ($currency) {
            $this->applyCurrencyFilter($salesOrdersDataQueryBuilder, $amountType, $currency);
        }

        return $salesOrdersDataQueryBuilder;
    }

    private function getAmountField(string $amountType, ?string $currency): string
    {
        $useBaseValue = null === $currency;

        return match ($amountType) {
            self::AMOUNT_TYPE_SUBTOTAL_WITH_DISCOUNT => $useBaseValue
                ? 'o.subtotalWithDiscounts'
                : 'o.subtotalWithDiscountsValue',
            self::AMOUNT_TYPE_SUBTOTAL => $useBaseValue
                ? 'o.baseSubtotalValue'
                : 'o.subtotalValue',
            self::AMOUNT_TYPE_TOTAL => $useBaseValue
                ? 'o.baseTotalValue'
                : 'o.totalValue',
            default => throw new \InvalidArgumentException(sprintf('Unsupported amount type "%s"', $amountType)),
        };
    }

    private function applyCurrencyFilter(QueryBuilder $qb, string $amountType, string $currency): void
    {
        $currencyField = match ($amountType) {
            self::AMOUNT_TYPE_SUBTOTAL_WITH_DISCOUNT,
            self::AMOUNT_TYPE_SUBTOTAL => 'o.subtotalCurrency',
            self::AMOUNT_TYPE_TOTAL => 'o.totalCurrency',
            default => null,
        };

        if ($currencyField !== null) {
            QueryBuilderUtil::checkParameter($currencyField);
            $qb->andWhere($qb->expr()->eq($currencyField, ':currency'))
                ->setParameter('currency', $currency);
        }
    }
}
