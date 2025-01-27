<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies query builders for the following entities to filter not accessible items:
 * * CheckoutLineItem
 * * CheckoutProductKitItemLineItem
 */
class CheckoutLineItemQueryModifier implements QueryModifierInterface
{
    public function __construct(
        private readonly EntityClassResolver $entityClassResolver,
        private readonly ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            switch ($this->entityClassResolver->getEntityClass($from->getFrom())) {
                case CheckoutLineItem::class:
                    $this->applyCheckoutLineItemRestriction($qb, $from->getAlias());
                    break;
                case CheckoutProductKitItemLineItem::class:
                    $this->applyCheckoutProductKitItemLineItemRestriction($qb, $from->getAlias());
                    break;
            }
        }
    }

    private function applyCheckoutLineItemRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $paramName = QueryBuilderUtil::generateParameterName('inventory_status', $qb);
        $qb
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull($rootAlias . '.product'),
                $qb->expr()->exists(\sprintf(
                    'SELECT 1 FROM %s pr WHERE pr = %s.product'
                    . ' AND JSON_EXTRACT(pr.serialized_data, \'inventory_status\') IN (:%s)',
                    Product::class,
                    $rootAlias,
                    $paramName
                ))
            ))
            ->setParameter($paramName, $this->getInventoryStatuses());
    }

    private function applyCheckoutProductKitItemLineItemRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $paramName = QueryBuilderUtil::generateParameterName('inventory_status', $qb);
        $qb
            ->andWhere($qb->expr()->exists(\sprintf(
                'SELECT 1 FROM %s pr WHERE pr = %s.product'
                . ' AND JSON_EXTRACT(pr.serialized_data, \'inventory_status\') IN (:%s)',
                Product::class,
                $rootAlias,
                $paramName
            )))
            ->setParameter($paramName, $this->getInventoryStatuses());
    }

    private function getInventoryStatuses(): array
    {
        return (array)$this->configManager->get('oro_order.frontend_product_visibility');
    }
}
