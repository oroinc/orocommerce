<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * Doctrine repository for Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal entity
 */
class ShoppingListTotalRepository extends EntityRepository
{
    /**
     * Invalidate ShoppingList subtotals by given Combined Price List ids
     */
    public function invalidateByCombinedPriceList(array $combinedPriceListIds)
    {
        $this->invalidateByBasePriceList($combinedPriceListIds, CombinedProductPrice::class);
    }

    /**
     * Invalidate ShoppingList subtotals by given Price List ids
     */
    public function invalidateByPriceList(array $priceListIds)
    {
        $this->invalidateByBasePriceList($priceListIds, ProductPrice::class);
    }

    /**
     * Invalidate ShoppingList subtotals by given Base Price List ids
     */
    protected function invalidateByBasePriceList(array $priceListIds, string $priceClass)
    {
        if (!$priceListIds) {
            return;
        }

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery->select('1')
            ->from(LineItem::class, 'lineItem')
            ->join(
                $priceClass,
                'productPrice',
                Join::WITH,
                $subQuery->expr()->eq('lineItem.product', 'productPrice.product')
            )
            ->where(
                $subQuery->expr()->eq('total.shoppingList', 'lineItem.shoppingList'),
                $subQuery->expr()->in('productPrice.priceList', ':priceLists')
            );

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update($this->getEntityName(), 'total')
            ->set('total.valid', ':newIsValid')
            ->where(
                $qb->expr()->eq('total.valid', ':isValid'),
                $qb->expr()->exists($subQuery)
            )
            ->setParameter('newIsValid', false, Types::BOOLEAN)
            ->setParameter('priceLists', $priceListIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('isValid', true, Types::BOOLEAN);

        $qb->getQuery()->execute();
    }

    /**
     * @param array $customerIds
     * @param int $websiteId
     */
    public function invalidateByCustomers(array $customerIds, $websiteId)
    {
        if (empty($customerIds)) {
            return;
        }

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $updateQB = $this->getBaseInvalidateQb($websiteId);
        $updateQB->andWhere($expr->in('sl.customer_id', ':customerIds'));
        $updateQB->setParameter('customerIds', $customerIds, Connection::PARAM_INT_ARRAY);

        $updateQB->execute();
    }

    /**
     * @param array $customerGroupIds
     * @param int $websiteId
     */
    public function invalidateByCustomerGroupsForFlatPricing(array $customerGroupIds, $websiteId)
    {
        if (empty($customerGroupIds)) {
            return;
        }

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $updateQB = $this->getBaseInvalidateQb($websiteId);
        $updateQB
            ->innerJoin('sl', 'oro_customer', 'cus', $expr->eq('sl.customer_id', 'cus.id'))
            ->innerJoin(
                'cus',
                'oro_price_list_to_cus_group',
                'pl2cg',
                $expr->andX(
                    $expr->eq('pl2cg.customer_group_id', 'cus.group_id'),
                    $expr->eq('pl2cg.website_id', 'sl.website_id')
                )
            )
            ->leftJoin(
                'cus',
                'oro_price_list_to_customer',
                'pl2cus',
                $expr->andX(
                    $expr->eq('pl2cus.customer_id', 'cus.id'),
                    $expr->eq('pl2cus.website_id', 'sl.website_id')
                )
            )
            ->andWhere(
                $expr->andX(
                    $expr->isNull('pl2cus.id'),
                    $expr->eq('sl.website_id', ':websiteId'),
                    $expr->in('cus.group_id', ':customerGroupIds')
                )
            )
            ->setParameter('websiteId', $websiteId, Types::INTEGER)
            ->setParameter('customerGroupIds', $customerGroupIds, Connection::PARAM_INT_ARRAY);

        $updateQB->execute();
    }

    public function invalidateByWebsitesForFlatPricing(array $websiteIds)
    {
        if (empty($websiteIds)) {
            return;
        }

        $updateQB = $this->getInvalidateQb();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $updateQB
            ->innerJoin('sl', 'oro_customer', 'cus', $expr->eq('sl.customer_id', 'cus.id'))
            ->leftJoin(
                'cus',
                'oro_price_list_to_cus_group',
                'pl2cg',
                $expr->andX(
                    $expr->eq('pl2cg.customer_group_id', 'cus.group_id'),
                    $expr->eq('pl2cg.website_id', 'sl.website_id')
                )
            )
            ->leftJoin(
                'cus',
                'oro_price_list_to_customer',
                'pl2cus',
                $expr->andX(
                    $expr->eq('pl2cus.customer_id', 'cus.id'),
                    $expr->eq('pl2cus.website_id', 'sl.website_id')
                )
            )
            ->andWhere(
                $expr->andX(
                    $expr->isNull('pl2cus.id'),
                    $expr->isNull('pl2cg.id'),
                    $expr->in('sl.website_id', ':websiteIds'),
                )
            )
            ->setParameter('websiteIds', $websiteIds, Connection::PARAM_INT_ARRAY);

        $updateQB->getQuery()->execute();
    }

    /**
     * @param int $websiteId
     */
    public function invalidateGuestShoppingLists($websiteId)
    {
        $visitorMetadata = $this->getEntityManager()->getClassMetadata(CustomerVisitor::class);
        $customerVisitorsJoinTable = $visitorMetadata->getAssociationMapping('shoppingLists')['joinTable']['name'];

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $visitorSubQB = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $visitorSubQB->select($visitorSubQB->expr()->literal(1, Types::INTEGER))
            ->from($customerVisitorsJoinTable, 'slv')
            ->where($expr->eq('slv.shoppinglist_id', 'sl.id'));

        $updateQB = $this->getBaseInvalidateQb($websiteId);
        $updateQB->andWhere($expr->exists($visitorSubQB->getSQL()));

        $updateQB->execute();
    }

    /**
     * Invalidate ShoppingList subtotals by given Product ids for website
     */
    public function invalidateByProducts(Website $website, array $productIds)
    {
        if (!$productIds) {
            return;
        }

        $lineItemMetadata = $this->getEntityManager()->getClassMetadata(LineItem::class);

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $subQuery = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subQuery->select($subQuery->expr()->literal(1, Types::INTEGER))
            ->from($lineItemMetadata->getTableName(), 'li')
            ->where(
                $subQuery->expr()->eq('li.shopping_list_id', 'sl.id'),
                $subQuery->expr()->in('li.product_id', ':products')
            );

        $updateQB = $this->getBaseInvalidateQb($website->getId());
        $updateQB->andWhere($expr->exists($subQuery->getSQL()));
        $updateQB->setParameter('products', $productIds, Connection::PARAM_INT_ARRAY);

        $updateQB->execute();
    }

    /**
     * Invalidate ShoppingList subtotals by given website
     */
    public function invalidateByWebsite(Website $website)
    {
        $this->getBaseInvalidateQb($website->getId())->execute();
    }

    /**
     * @param int $websiteId
     * @return SqlQueryBuilder
     */
    protected function getBaseInvalidateQb($websiteId): SqlQueryBuilder
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();
        $updateQB = $this->getInvalidateQb();
        $updateQB->andWhere($expr->eq('sl.website_id', ':websiteId'))
            ->setParameter('websiteId', $websiteId, Types::INTEGER);

        return $updateQB;
    }

    protected function getInvalidateQb(): SqlQueryBuilder
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();
        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $this->getEntityManager()->getConnection()->getDatabasePlatform()
        );
        $updateQB = new SqlQueryBuilder($this->getEntityManager(), $rsm);
        $updateQB->update('oro_shopping_list_total', 'st')
            ->innerJoin('st', 'oro_shopping_list', 'sl', $expr->eq('st.shopping_list_id', 'sl.id'))
            ->set('is_valid', ':newIsValid')
            ->where(
                $expr->eq('st.is_valid', ':isValid')
            )
            ->setParameter('newIsValid', false, Types::BOOLEAN)
            ->setParameter('isValid', true, Types::BOOLEAN);

        return $updateQB;
    }
}
