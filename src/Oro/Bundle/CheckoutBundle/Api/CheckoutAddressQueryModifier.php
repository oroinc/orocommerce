<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierEntityJoinTrait;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierOptionsAwareInterface;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies query builder for checkout address entity to filter data
 * that should not be accessible via API for the storefront.
 */
class CheckoutAddressQueryModifier implements QueryModifierInterface, QueryModifierOptionsAwareInterface
{
    use QueryModifierEntityJoinTrait;

    private ?array $options = null;

    public function __construct(
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

        if (CheckoutAddress::class !== ($this->options['resourceClass'] ?? null)) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->entityClassResolver->getEntityClass($from->getFrom());
            if (OrderAddress::class === $entityClass) {
                $this->applyCheckoutAddressRootRestriction($qb, $from->getAlias());
            }
        }
    }

    private function applyCheckoutAddressRootRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        $checkoutAlias = $this->ensureEntityJoined(
            $qb,
            'checkout',
            Checkout::class,
            \sprintf('{joinAlias}.billingAddress = %1$s or {joinAlias}.shippingAddress = %1$s', $rootAlias)
        );
        $paramName = QueryBuilderUtil::generateParameterName('deleted', $qb);
        $qb
            ->andWhere($qb->expr()->eq($checkoutAlias . '.deleted', ':' . $paramName))
            ->setParameter($paramName, false);
    }
}
