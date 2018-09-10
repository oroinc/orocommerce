<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity as ParentLoadNormalizedEntity;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * After update/create "get" request happens and to properly send context id
 * we modify it back to guid-pricelist format.
 */
class LoadNormalizedProductPriceWithNormalizedId extends ParentLoadNormalizedEntity
{
    /**
     * {@inheritdoc}
     */
    protected function createGetContext(SingleItemContext $context, ActionProcessorInterface $processor)
    {
        $getContext = parent::createGetContext($context, $processor);
        $getContext->setId(PriceListIdContextUtil::normalizeProductPriceId($context, $getContext->getId()));

        return $getContext;
    }
}
