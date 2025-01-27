<?php

namespace Oro\Bundle\OrderBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierOptionsAwareInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Modifies query builder for order address entity to filter data
 * that should not be accessible via API for the storefront.
 */
class OrderAddressQueryModifier implements QueryModifierInterface, QueryModifierOptionsAwareInterface
{
    private EntityClassResolver $entityClassResolver;
    private ?array $options = null;

    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
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

        $resourceClass = $this->options['resourceClass'] ?? null;
        if ($resourceClass && OrderAddress::class !== $resourceClass) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->entityClassResolver->getEntityClass($from->getFrom());
            if (OrderAddress::class === $entityClass) {
                $this->addOrderSubquery($qb, $from->getAlias());
            }
        }
        foreach ($qb->getDQLPart('join') as $joins) {
            /** @var Expr\Join $join */
            foreach ($joins as $join) {
                if (OrderAddress::class === $join->getJoin()) {
                    $this->addOrderSubquery($qb, $join->getAlias());
                }
            }
        }
    }

    private function addOrderSubquery(QueryBuilder $qb, string $addressAlias): void
    {
        $qb->andWhere($qb->expr()->exists(\sprintf(
            'SELECT 1 FROM %1$s ord WHERE (ord.billingAddress = %2$s or ord.shippingAddress = %2$s)',
            Order::class,
            $addressAlias
        )));
    }
}
