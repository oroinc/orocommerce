<?php

namespace Oro\Bundle\OrderBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Modifies query builder for order line items to filter data
 * that should not be accessible via API for the storefront.
 * This class can be implemented as a rule for AclHelper after this component
 * will allow to add rules for associated entities (BAP-17680). In this case we will modify AST of a query
 * instead of modifying QueryBuilder; this solution is more flexible and more error-free
 * because we will work with already parsed query, instead of trying to parse it manually.
 */
class OrderLineItemQueryModifier implements QueryModifierInterface
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
            if (OrderLineItem::class === $entityClass) {
                $this->ensureOrderInnerJoinExists($qb, $from->getAlias());
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootAlias
     */
    private function ensureOrderInnerJoinExists(QueryBuilder $qb, string $rootAlias): void
    {
        $orderJoin = $this->getOrderJoin($qb, $rootAlias);
        if (null === $orderJoin) {
            $orderJoinAlias = 'order';
            QueryBuilderUtil::checkIdentifier($rootAlias);
            $qb->innerJoin($rootAlias . '.order', $orderJoinAlias);
        } elseif (Expr\Join::INNER_JOIN !== $orderJoin->getJoinType()) {
            $joinTypeProp = ReflectionUtil::getProperty(new \ReflectionClass($orderJoin), 'joinType');
            if (null === $joinTypeProp) {
                throw new \LogicException(sprintf(
                    'The class "%s" does not have "joinType" property.',
                    \get_class($orderJoin)
                ));
            }
            $joinTypeProp->setAccessible(true);
            $joinTypeProp->setValue($orderJoin, Expr\Join::INNER_JOIN);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootAlias
     *
     * @return Expr\Join|null
     */
    private function getOrderJoin(QueryBuilder $qb, string $rootAlias): ?Expr\Join
    {
        $result = null;
        /** @var Expr\Join[] $joins */
        foreach ($qb->getDQLPart('join') as $joinGroupAlias => $joins) {
            if ($joinGroupAlias !== $rootAlias) {
                continue;
            }
            $expectedJoin = \sprintf('%s.order', $rootAlias);
            foreach ($joins as $join) {
                if ($join->getJoin() === $expectedJoin) {
                    $result = $join;
                    break;
                }
            }
        }

        return $result;
    }
}
