<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Resets the price rule for a product price if the product price is changed
 * and removes the "product_price" attribute from the context of Batch API items.
 */
class BatchResetPriceRuleField implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var BatchUpdateContext $context */

        $items = $context->getBatchItems();
        foreach ($items as $item) {
            if ($item->getContext()->getTargetAction() !== ApiAction::UPDATE) {
                continue;
            }

            $itemTargetContext = $item->getContext()->getTargetContext();
            if (null === $itemTargetContext) {
                continue;
            }

            if (!is_a($itemTargetContext->getClassName(), ProductPrice::class, true)) {
                continue;
            }

            $productPrice = $itemTargetContext->getResult();
            if (null === $productPrice) {
                continue;
            }

            $oldProductPrice = $itemTargetContext->get(RememberProductPrice::PRODUCT_PRICE_ATTRIBUTE);
            if (null === $oldProductPrice) {
                continue;
            }

            if ($this->needToResetPriceRule($productPrice, $oldProductPrice)) {
                $productPrice->setPriceRule(null);
            }
            $itemTargetContext->remove(RememberProductPrice::PRODUCT_PRICE_ATTRIBUTE);
        }
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
