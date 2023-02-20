<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a clone of the product price from the "result" context attribute
 * to the "product_price" context attribute.
 */
class RememberProductPrice implements ProcessorInterface
{
    public const PRODUCT_PRICE_ATTRIBUTE = 'product_price';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var ProductPrice|null $entity */
        $productPrice = $context->getForm()->getData();
        if (null === $productPrice) {
            return;
        }

        if (!$context->has(self::PRODUCT_PRICE_ATTRIBUTE)) {
            $context->set(self::PRODUCT_PRICE_ATTRIBUTE, clone $productPrice);
        }
    }
}
