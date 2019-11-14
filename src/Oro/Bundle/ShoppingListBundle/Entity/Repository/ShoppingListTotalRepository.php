<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
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
     *
     * @param array $combinedPriceListIds
     */
    public function invalidateByCombinedPriceList(array $combinedPriceListIds)
    {
        if (!$combinedPriceListIds) {
            return;
        }

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery->select('1')
            ->from('OroShoppingListBundle:LineItem', 'lineItem')
            ->join(
                'OroPricingBundle:CombinedProductPrice',
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
            ->setParameter('newIsValid', false, Type::BOOLEAN)
            ->setParameter('priceLists', $combinedPriceListIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('isValid', true, Type::BOOLEAN);

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
        $updateQB = $this->getBaseInvalidateUpdateQb($websiteId);
        $updateQB->andWhere($expr->in('sl.customer_id', ':customerIds'));
        $updateQB->setParameter('customerIds', $customerIds, Connection::PARAM_INT_ARRAY);

        $updateQB->execute();
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
        $visitorSubQB->select($visitorSubQB->expr()->literal(1, Type::INTEGER))
            ->from($customerVisitorsJoinTable, 'slv')
            ->where($expr->eq('slv.shoppinglist_id', 'sl.id'));

        $updateQB = $this->getBaseInvalidateUpdateQb($websiteId);
        $updateQB->andWhere($expr->exists($visitorSubQB->getSQL()));

        $updateQB->execute();
    }

    /**
     * Invalidate ShoppingList subtotals by given Product ids for website
     *
     * @param Website $website
     * @param array $productIds
     */
    public function invalidateByProducts(Website $website, array $productIds)
    {
        if (!$productIds) {
            return;
        }

        $lineItemMetadata = $this->getEntityManager()->getClassMetadata(LineItem::class);

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $subQuery = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subQuery->select($subQuery->expr()->literal(1, Type::INTEGER))
            ->from($lineItemMetadata->getTableName(), 'li')
            ->where(
                $subQuery->expr()->eq('li.shopping_list_id', 'sl.id'),
                $subQuery->expr()->in('li.product_id', ':products')
            );

        $updateQB = $this->getBaseInvalidateNativeQb($website->getId());
        $updateQB->andWhere($expr->exists($subQuery->getSQL()));
        $updateQB->setParameter('products', $productIds, Connection::PARAM_INT_ARRAY);

        $updateQB->execute();
    }

    /**
     * Invalidate ShoppingList subtotals by given website
     *
     * @param Website $website
     */
    public function invalidateByWebsite(Website $website)
    {
        $this->getBaseInvalidateNativeQb($website->getId())->execute();
    }

    /**
     * @param int $websiteId
     * @return SqlQueryBuilder
     */
    protected function getBaseInvalidateNativeQb($websiteId): SqlQueryBuilder
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
                $expr->andX(
                    $expr->eq('st.is_valid', ':isValid'),
                    $expr->eq('sl.website_id', ':websiteId')
                )
            )
            ->setParameter('newIsValid', false, Type::BOOLEAN)
            ->setParameter('isValid', true, Type::BOOLEAN)
            ->setParameter('websiteId', $websiteId, Type::INTEGER);
        return $updateQB;
    }

    /**
     * @param int $websiteId
     * @return SqlQueryBuilder
     */
    protected function getBaseInvalidateUpdateQb($websiteId)
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
                $expr->andX(
                    $expr->eq('st.is_valid', ':isValid'),
                    $expr->eq('sl.website_id', ':websiteId')
                )
            )
            ->setParameter('newIsValid', false, Type::BOOLEAN)
            ->setParameter('isValid', true, Type::BOOLEAN)
            ->setParameter('websiteId', $websiteId, Type::INTEGER);

        return $updateQB;
    }

    /**
     * @param int $websiteId
     * @return QueryBuilder
     */
    protected function getBaseInvalidateQb($websiteId)
    {
        $qb = $this->createQueryBuilder('total');
        $qb->select('DISTINCT total.id')
            ->join('total.shoppingList', 'shoppingList')
            ->andWhere($qb->expr()->eq('shoppingList.website', ':website'))
            ->andWhere($qb->expr()->eq('total.valid', ':isValid'))
            ->setParameter('website', $websiteId)
            ->setParameter('isValid', true);

        return $qb;
    }

    /**
     * @param \Iterator $iterator
     */
    protected function invalidateTotals(\Iterator $iterator)
    {
        $ids = [];
        $qbUpdate = $this->_em->createQueryBuilder();
        $qbUpdate->update($this->_entityName, 'total')
            ->where($qbUpdate->expr()->in('total.id', ':totalIds'))
            ->set('total.valid', ':valid')
            ->setParameter('valid', false);
        $i = 0;
        foreach ($iterator as $total) {
            $ids[] = $total['id'];
            $i++;
            if ($i % 500 === 0) {
                $qbUpdate->setParameter('totalIds', $ids)
                    ->getQuery()
                    ->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbUpdate->setParameter('totalIds', $ids)
                ->getQuery()
                ->execute();
        }
    }
}
