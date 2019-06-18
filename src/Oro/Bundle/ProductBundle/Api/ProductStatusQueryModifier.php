<?php

namespace Oro\Bundle\ProductBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies a product query builder to filter disabled products,
 * because they should not be accessible via API for the storefront.
 * @see \Oro\Bundle\ProductBundle\Acl\Voter\ProductStatusVoter
 * This class can be implemented as a rule for AclHelper after this component
 * will allow to add additional rules for it (BAP-10836). In this case we will modify AST of a query
 * instead of modifying QueryBuilder; this solution is more flexible and more error-free
 * because we will work with already parsed query, instead of trying to parse it manually.
 */
class ProductStatusQueryModifier implements QueryModifierInterface
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
            if (Product::class === $this->entityClassResolver->getEntityClass($from->getFrom())) {
                $this->applyRootRestriction($qb, $from->getAlias());
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootAlias
     */
    private function applyRootRestriction(QueryBuilder $qb, string $rootAlias): void
    {
        QueryBuilderUtil::checkIdentifier($rootAlias);
        $paramName = QueryBuilderUtil::generateParameterName('status');
        $qb
            ->andWhere($qb->expr()->eq($rootAlias . '.status', ':' . $paramName))
            ->setParameter($paramName, Product::STATUS_ENABLED);
    }
}
