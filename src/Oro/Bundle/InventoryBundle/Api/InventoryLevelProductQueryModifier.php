<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierOptionsAwareInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Modifies query builders for the InventoryLevel entity to filter inventory levels for not accessible products.
 */
class InventoryLevelProductQueryModifier implements QueryModifierInterface, QueryModifierOptionsAwareInterface
{
    private ?array $options = null;

    public function __construct(
        private readonly EntityClassResolver $entityClassResolver,
        private readonly QueryModifierRegistry $queryModifierRegistry
    ) {
    }

    #[\Override]
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    #[\Override]
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->entityClassResolver->getEntityClass($from->getFrom());
            if (InventoryLevel::class === $entityClass) {
                $this->applyProductRestriction($qb, $from->getAlias());
            }
        }
    }

    private function applyProductRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $productQb = $qb->getEntityManager()->createQueryBuilder()
            ->from(Product::class, 'inventory_level_product')
            ->select('1')
            ->where(\sprintf('inventory_level_product = %s.product', $rootAlias));
        $this->queryModifierRegistry->modifyQuery($productQb, false, $this->options['requestType'], $this->options);

        $qb->andWhere($qb->expr()->exists($productQb->getDQL()));
        foreach ($productQb->getParameters() as $parameter) {
            $qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
        }
    }
}
