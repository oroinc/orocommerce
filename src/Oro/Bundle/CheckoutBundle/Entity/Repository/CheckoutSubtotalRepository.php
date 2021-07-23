<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * Repository for CheckoutSubtotal entity
 */
class CheckoutSubtotalRepository extends EntityRepository
{
    /**
     * Invalidate checkout subtotals by given Combined Price List ids
     */
    public function invalidateByCombinedPriceList(array $combinedPriceListIds)
    {
        $this->invalidateByPriceListRelation('combined_price_list_id', $combinedPriceListIds);
    }

    /**
     * Invalidate checkout subtotals by given Price List ids
     */
    public function invalidateByPriceList(array $priceListIds)
    {
        $this->invalidateByPriceListRelation('price_list_id', $priceListIds);
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

        $updateQB = $this->getBaseInvalidateByRelationQueryBuilder();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $updateQB
            ->andWhere(
                $expr->andX(
                    $expr->eq('c.website_id', ':websiteId'),
                    $expr->in('c.customer_id', ':customerIds')
                )
            )
            ->setParameter('websiteId', $websiteId, Types::INTEGER)
            ->setParameter('customerIds', $customerIds, Connection::PARAM_INT_ARRAY);

        $updateQB->getQuery()->execute();
    }

    /**
     * @param array $customerGroupIds
     * @param int $websiteId
     */
    public function invalidateByCustomerGroups(array $customerGroupIds, $websiteId)
    {
        if (empty($customerGroupIds)) {
            return;
        }

        $updateQB = $this->getBaseInvalidateByRelationQueryBuilder();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $updateQB
            ->innerJoin('c', 'oro_customer', 'cus', $expr->eq('c.customer_id', 'cus.id'))
            ->innerJoin(
                'cus',
                'oro_price_list_to_cus_group',
                'pl2cg',
                $expr->andX(
                    $expr->eq('pl2cg.customer_group_id', 'cus.group_id'),
                    $expr->eq('pl2cg.website_id', 'c.website_id')
                )
            )
            ->leftJoin(
                'cus',
                'oro_price_list_to_customer',
                'pl2cus',
                $expr->andX(
                    $expr->eq('pl2cus.customer_id', 'cus.id'),
                    $expr->eq('pl2cus.website_id', 'c.website_id')
                )
            )
            ->andWhere(
                $expr->andX(
                    $expr->isNull('pl2cus.id'),
                    $expr->eq('c.website_id', ':websiteId'),
                    $expr->in('cus.group_id', ':customerGroupIds')
                )
            )
            ->setParameter('websiteId', $websiteId, Types::INTEGER)
            ->setParameter('customerGroupIds', $customerGroupIds, Connection::PARAM_INT_ARRAY);

        $updateQB->getQuery()->execute();
    }

    public function invalidateByWebsites(array $websiteIds)
    {
        if (empty($websiteIds)) {
            return;
        }

        $updateQB = $this->getBaseInvalidateByRelationQueryBuilder();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $updateQB
            ->innerJoin('c', 'oro_customer', 'cus', $expr->eq('c.customer_id', 'cus.id'))
            ->leftJoin(
                'cus',
                'oro_price_list_to_cus_group',
                'pl2cg',
                $expr->andX(
                    $expr->eq('pl2cg.customer_group_id', 'cus.group_id'),
                    $expr->eq('pl2cg.website_id', 'c.website_id')
                )
            )
            ->leftJoin(
                'cus',
                'oro_price_list_to_customer',
                'pl2cus',
                $expr->andX(
                    $expr->eq('pl2cus.customer_id', 'cus.id'),
                    $expr->eq('pl2cus.website_id', 'c.website_id')
                )
            )
            ->andWhere(
                $expr->andX(
                    $expr->isNull('pl2cus.id'),
                    $expr->isNull('pl2cg.id'),
                    $expr->in('c.website_id', ':websiteIds'),
                )
            )
            ->setParameter('websiteIds', $websiteIds, Connection::PARAM_INT_ARRAY);

        $updateQB->getQuery()->execute();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function invalidateByPriceListRelation(string $relationColumn, array $priceListIds)
    {
        if (!$priceListIds) {
            return;
        }

        $updateQB = $this->getBaseInvalidateByRelationQueryBuilder();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $updateQB
            ->andWhere(
                $expr->in(QueryBuilderUtil::getField('cs', $relationColumn), ':priceListIds')
            )
            ->setParameter('priceListIds', $priceListIds, Connection::PARAM_INT_ARRAY);

        $updateQB->getQuery()->execute();
    }

    /**
     * @return SqlQueryBuilder
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getBaseInvalidateByRelationQueryBuilder()
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $lineItemSubQB = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $lineItemSubQB->select($expr->literal(1))
            ->from('oro_checkout_line_item', 'li')
            ->where($expr->eq('li.checkout_id', 'c.id'))
            ->andWhere($expr->eq('li.is_price_fixed', ':isFixed'));

        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $this->getEntityManager()->getConnection()->getDatabasePlatform()
        );
        $updateQB = new SqlQueryBuilder($this->getEntityManager(), $rsm);
        $updateQB->update('oro_checkout_subtotal', 'cs')
            ->innerJoin('cs', 'oro_checkout', 'c', $expr->eq('cs.checkout_id', 'c.id'))
            ->set('is_valid', ':newIsValid')
            ->where(
                $expr->andX(
                    $expr->eq('cs.is_valid', ':isValid'),
                    $expr->eq('c.deleted', ':isDeleted'),
                    $expr->eq('c.completed', ':isCompleted'),
                    $expr->exists($lineItemSubQB->getSQL())
                )
            );
        $updateQB->setParameters(
            [
                'newIsValid' => false,
                'isValid' => true,
                'isFixed' => false,
                'isDeleted' => false,
                'isCompleted' => false,
            ],
            [
                'newIsValid' => Types::BOOLEAN,
                'isValid' => Types::BOOLEAN,
                'isFixed' => Types::BOOLEAN,
                'isDeleted' => Types::BOOLEAN,
                'isCompleted' => Types::BOOLEAN
            ],
        );

        return $updateQB;
    }
}
