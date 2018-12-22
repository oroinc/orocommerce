<?php

namespace Oro\Bundle\OrderBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Modifies query builder for order address entity to filter data
 * that should not be accessible via API for the storefront.
 * This class can be implemented as a rule for AclHelper after this component
 * will allow to add rules for associated entities (BAP-17680). In this case we will modify AST of a query
 * instead of modifying QueryBuilder; this solution is more flexible and more error-free
 * because we will work with already parsed query, instead of trying to parse it manually.
 */
class OrderAddressQueryModifier implements QueryModifierInterface
{
    /** @var EntityClassResolver */
    private $entityClassResolver;

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
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

    /**
     * @param QueryBuilder $qb
     * @param string $addressAlias
     */
    private function addOrderSubquery(QueryBuilder $qb, string $addressAlias)
    {
        $subquery = sprintf(
            'SELECT 1 FROM %s ord WHERE (ord.billingAddress = %s or ord.shippingAddress = %s)',
            Order::class,
            $addressAlias,
            $addressAlias
        );
        $qb->andWhere($qb->expr()->exists($subquery));
    }
}
