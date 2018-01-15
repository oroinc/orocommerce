<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ResetPriceRuleFieldOnUpdate implements ProcessorInterface
{
    /**
     * @var ProductPrice
     */
    private $oldProductPrice;

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context)
    {
        $productPrice = $context->getResult();
        if (!$productPrice instanceof ProductPrice) {
            return;
        }

        if ($this->oldProductPrice === null) {
            $this->oldProductPrice = clone $productPrice;

            return;
        }

        if ($this->needToResetPriceRule($productPrice)) {
            $productPrice->setPriceRule(null);
        }

        $this->oldProductPrice = null;
    }

    /**
     * @param ProductPrice $productPrice
     *
     * @return bool
     */
    private function needToResetPriceRule(ProductPrice $productPrice): bool
    {
        return $productPrice->getQuantity() !== $this->oldProductPrice->getQuantity() ||
            $productPrice->getProductUnit() !== $this->oldProductPrice->getProductUnit() ||
            $productPrice->getPrice()->getCurrency() !== $this->oldProductPrice->getPrice()->getCurrency() ||
            $productPrice->getPrice()->getValue() != $this->oldProductPrice->getPrice()->getValue();
    }
}
