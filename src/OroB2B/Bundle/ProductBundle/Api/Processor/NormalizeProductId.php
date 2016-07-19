<?php

namespace OroB2B\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class NormalizeProductId implements ProcessorInterface
{
    const PRODUCT_IDENTIFIER = 'sku';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var UpdateContext $context */
        $productRequestData = [self::PRODUCT_IDENTIFIER => $context->getId()];
        $context->setRequestData(array_merge($productRequestData, $context->getRequestData()));
        $context->setId(null);
    }
}
