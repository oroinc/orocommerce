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
 * This class can be implemented as a rule for AclHelper after this component
 * will allow to add additional rules for it (BAP-10836). In this case we will modify AST of a query
 * instead of modifying QueryBuilder; this solution is more flexible and more error-free
 * because we will work with already parsed query, instead of trying to parse it manually.
 */
class ProductVisibilityQueryModifier implements QueryModifierInterface
{
    /** @var EntityClassResolver */
    private $entityClassResolver;

    /** @var ProductVisibilityQueryBuilderModifier */
    private $modifier;

    /**
     * @param EntityClassResolver                   $entityClassResolver
     * @param ProductVisibilityQueryBuilderModifier $modifier
     */
    public function __construct(
        EntityClassResolver $entityClassResolver,
        ProductVisibilityQueryBuilderModifier $modifier
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->modifier = $modifier;
    }

    /**
     * {@inheritdoc}
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
