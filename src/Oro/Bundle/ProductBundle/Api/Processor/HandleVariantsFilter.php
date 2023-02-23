<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Modifies ORM query from the context to filter simple products that represent
 * individual variations of a configurable products in case the "variants" filter exists
 * and its value is FALSE.
 */
class HandleVariantsFilter implements ProcessorInterface
{
    private const FILTER_NAME = 'variants';

    private QueryBuilderModifierInterface $modifier;

    public function __construct(QueryBuilderModifierInterface $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->getFilters()->has(self::FILTER_NAME)) {
            // the "variants" filter is not supported
            return;
        }

        $filterValue = $context->getFilterValues()->get(self::FILTER_NAME);
        if (null === $filterValue || $filterValue->getValue()) {
            // the filtering of variants for configurable products was not requested
            return;
        }

        $query = $context->getQuery();
        if ($query instanceof QueryBuilder) {
            $this->modifier->modify($query);
        }
    }
}
