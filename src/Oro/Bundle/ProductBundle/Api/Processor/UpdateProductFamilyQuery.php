<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies the query for AttributeFamily entity to filter not product families.
 */
class UpdateProductFamilyQuery implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unexpected query type
            return;
        }

        $query->andWhere(sprintf(
            '%s.entityClass = :productEntityClass',
            QueryBuilderUtil::getSingleRootAlias($query)
        ));
        $query->setParameter('productEntityClass', Product::class);
    }
}
