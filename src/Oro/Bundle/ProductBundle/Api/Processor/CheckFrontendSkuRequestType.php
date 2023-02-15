<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "frontend_sku" request type to the context if the "X-Product-ID" header equals to "sku".
 */
class CheckFrontendSkuRequestType implements ProcessorInterface
{
    private const REQUEST_HEADER_NAME = 'X-Product-ID';
    private const REQUEST_HEADER_VALUE = 'sku';
    private const REQUEST_TYPE = 'frontend_sku';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $requestType = $context->getRequestType();
        if (!$requestType->contains(self::REQUEST_TYPE)
            && self::REQUEST_HEADER_VALUE === $context->getRequestHeaders()->get(self::REQUEST_HEADER_NAME)
        ) {
            $requestType->add(self::REQUEST_TYPE);
        }
    }
}
