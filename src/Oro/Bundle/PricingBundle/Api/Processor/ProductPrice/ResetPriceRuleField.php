<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Resets the price rule for a product price if the product price is changed
 * and removes the "product_price" attribute from the context.
 */
class ResetPriceRuleField implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var ProductPrice|null $entity */
        $productPrice = $context->getData();
        if (null === $productPrice) {
            return;
        }

        $oldProductPrice = $context->get(RememberProductPrice::PRODUCT_PRICE_ATTRIBUTE);
        if (null === $oldProductPrice) {
            return;
        }

        if ($this->needToResetPriceRule($productPrice, $oldProductPrice)) {
            $productPrice->setPriceRule(null);
        }
        $context->remove(RememberProductPrice::PRODUCT_PRICE_ATTRIBUTE);
    }

    private function needToResetPriceRule(ProductPrice $productPrice1, ProductPrice $productPrice2): bool
    {
        return
            $productPrice1->getQuantity() !== $productPrice2->getQuantity()
            || $productPrice1->getProductUnit() !== $productPrice2->getProductUnit()
            || $productPrice1->getPrice()->getCurrency() !== $productPrice2->getPrice()->getCurrency()
            || $productPrice1->getPrice()->getValue() != $productPrice2->getPrice()->getValue();
    }
}
