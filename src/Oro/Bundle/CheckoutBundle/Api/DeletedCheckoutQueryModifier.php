<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierEntityJoinTrait;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies query builders for the following entities to filter deleted checkouts,
 * because they should not be accessible via API for the storefront:
 * * Checkout
 * * CheckoutLineItem
 * * CheckoutProductKitItemLineItem
 */
class DeletedCheckoutQueryModifier implements QueryModifierInterface
{
    use QueryModifierEntityJoinTrait;

    public function __construct(
        private readonly EntityClassResolver $entityClassResolver
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
                case Checkout::class:
                    $this->applyCheckoutRestriction($qb, $from->getAlias());
                    break;
                case CheckoutLineItem::class:
                    $this->applyCheckoutLineItemRestriction($qb, $from->getAlias());
                    break;
                case CheckoutProductKitItemLineItem::class:
                    $this->applyCheckoutProductKitItemLineItemRestriction($qb, $from->getAlias());
                    break;
            }
        }
    }

    private function applyCheckoutRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $paramName = QueryBuilderUtil::generateParameterName('deleted', $qb);
        $qb
            ->andWhere($qb->expr()->eq($rootAlias . '.deleted', ':' . $paramName))
            ->setParameter($paramName, false);
    }

    private function applyCheckoutLineItemRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $checkoutAlias = $this->ensureEntityJoined($qb, 'checkout', $rootAlias . '.checkout');
        $this->applyCheckoutRestriction($qb, $checkoutAlias);
    }

    private function applyCheckoutProductKitItemLineItemRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $checkoutLineItemAlias = $this->ensureEntityJoined($qb, 'lineItem', $rootAlias . '.lineItem');
        $checkoutAlias = $this->ensureEntityJoined($qb, 'checkout', $checkoutLineItemAlias . '.checkout');
        $this->applyCheckoutRestriction($qb, $checkoutAlias);
    }
}
