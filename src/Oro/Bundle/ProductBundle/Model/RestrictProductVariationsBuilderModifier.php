<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;

/**
 * Modify main query by adding condition to the it
 * that filters out all products that are variation of configurable product
 */
class RestrictProductVariationsBuilderModifier implements QueryBuilderModifierInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function modify(QueryBuilder $queryBuilder)
    {
        list($rootAlias) = $queryBuilder->getRootAliases();

        $variantLinkSubQuery = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductVariantLink::class)
            ->createQueryBuilder('vl');

        $variantLinkSubQuery
            ->select('1')
            ->where($variantLinkSubQuery->expr()->eq('vl.product', $rootAlias));

        $queryBuilder->andWhere(
            $queryBuilder->expr()->not(
                $queryBuilder->expr()->exists($variantLinkSubQuery->getDQL())
            )
        );
    }
}
