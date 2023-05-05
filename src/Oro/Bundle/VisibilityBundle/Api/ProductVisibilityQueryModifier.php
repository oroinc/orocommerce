<?php

namespace Oro\Bundle\VisibilityBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

/**
 * Modifies a product query builder to filter invisible products,
 * because they should not be accessible via API for the storefront.
 * @see \Oro\Bundle\VisibilityBundle\Acl\Voter\ProductVisibilityVoter
 */
class ProductVisibilityQueryModifier implements QueryModifierInterface
{
    private EntityClassResolver $entityClassResolver;
    private ProductVisibilityQueryBuilderModifier $modifier;

    public function __construct(
        EntityClassResolver $entityClassResolver,
        ProductVisibilityQueryBuilderModifier $modifier
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->modifier = $modifier;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        $isSupported = false;
        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            if (Product::class === $this->entityClassResolver->getEntityClass($from->getFrom())) {
                $isSupported = true;
                break;
            }
        }
        if ($isSupported) {
            $this->modifier->modify($qb);
        }
    }
}
