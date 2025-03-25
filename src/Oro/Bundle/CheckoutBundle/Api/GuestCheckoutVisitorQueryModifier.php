<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierEntityJoinTrait;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierOptionsAwareInterface;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies query builders for the following entities to filter entities belongs to the current visitor
 * when the current security context represents a visitor and the checkout feature is enabled for visitors:
 * * checkout
 * * checkout line item
 * * checkout product kit item line item
 * * checkout address
 */
class GuestCheckoutVisitorQueryModifier implements QueryModifierInterface, QueryModifierOptionsAwareInterface
{
    use QueryModifierEntityJoinTrait;

    private ?array $options = null;

    public function __construct(
        private readonly GuestCheckoutChecker $guestCheckoutChecker,
        private readonly EntityClassResolver $entityClassResolver
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

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->entityClassResolver->getEntityClass($from->getFrom());
            if (Checkout::class === $entityClass) {
                $this->applyCheckoutRootRestriction($qb, $from->getAlias());
            } elseif (CheckoutLineItem::class === $entityClass) {
                $this->applyCheckoutLineItemRootRestriction($qb, $from->getAlias());
            } elseif (CheckoutProductKitItemLineItem::class === $entityClass) {
                $this->applyCheckoutKitItemLineItemRootRestriction($qb, $from->getAlias());
            } elseif (OrderAddress::class === $entityClass
                && CheckoutAddress::class === ($this->options['resourceClass'] ?? null)
            ) {
                $this->applyCheckoutAddressRootRestriction($qb, $from->getAlias());
            }
        }
    }

    protected function getCheckoutSourceRestrictions(
        QueryBuilder $qb,
        string $checkoutSourceAlias,
        string $visitorParamName
    ): array {
        return [
            $qb->expr()->exists(\sprintf(
                'SELECT 1 FROM %s AS sl WHERE sl = %s.shoppingList AND :%s MEMBER OF sl.visitors',
                ShoppingList::class,
                $checkoutSourceAlias,
                $visitorParamName
            )),
            $qb->expr()->exists(\sprintf(
                'SELECT 1 FROM %s AS qd WHERE qd = %s.quoteDemand AND qd.visitor = :%s',
                QuoteDemand::class,
                $checkoutSourceAlias,
                $visitorParamName
            ))
        ];
    }

    private function applyCheckoutRootRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $visitor = $this->guestCheckoutChecker->getVisitor();
        if (!$visitor->getId()) {
            // an anonymous customer visitor cannot have checkouts
            $qb->andWhere('1 = 0');
        } else {
            $checkoutSourceAlias = $this->ensureEntityJoined($qb, 'checkoutSource', $rootAlias . '.source');
            $paramName = QueryBuilderUtil::generateParameterName('visitor', $qb);
            $qb
                ->andWhere($qb->expr()->orX(
                    ...$this->getCheckoutSourceRestrictions($qb, $checkoutSourceAlias, $paramName)
                ))
                ->setParameter($paramName, $visitor);
        }
    }

    private function applyCheckoutLineItemRootRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $checkoutAlias = $this->ensureEntityJoined($qb, 'checkout', $rootAlias . '.checkout');
        $this->applyCheckoutRootRestriction($qb, $checkoutAlias);
    }

    private function applyCheckoutKitItemLineItemRootRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $checkoutLineItemAlias = $this->ensureEntityJoined($qb, 'lineItem', $rootAlias . '.lineItem');
        $checkoutAlias = $this->ensureEntityJoined($qb, 'checkout', $checkoutLineItemAlias . '.checkout');
        $this->applyCheckoutRootRestriction($qb, $checkoutAlias);
    }

    private function applyCheckoutAddressRootRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $checkoutAlias = $this->ensureEntityJoined(
            $qb,
            'checkout',
            Checkout::class,
            \sprintf('{joinAlias}.billingAddress = %1$s or {joinAlias}.shippingAddress = %1$s', $rootAlias)
        );
        $this->applyCheckoutRootRestriction($qb, $checkoutAlias);
    }
}
