<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\QueryModifierRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies the query for ProductImage entity to filter images for not accessible products.
 */
class UpdateProductImageQuery implements ProcessorInterface
{
    private QueryModifierRegistry $queryModifier;

    public function __construct(QueryModifierRegistry $queryModifier)
    {
        $this->queryModifier = $queryModifier;
    }

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

        $subquery = $query->getEntityManager()
            ->createQueryBuilder()
            ->from(Product::class, 'p')
            ->select('p')
            ->where(sprintf('p = %s.product', QueryBuilderUtil::getSingleRootAlias($query)));
        $this->queryModifier->modifyQuery($subquery, false, $context->getRequestType());

        $query->andWhere($query->expr()->exists($subquery->getDQL()));
        /** @var Parameter[] $parameters */
        $parameters = $subquery->getParameters();
        foreach ($parameters as $parameter) {
            $query->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }
    }
}
