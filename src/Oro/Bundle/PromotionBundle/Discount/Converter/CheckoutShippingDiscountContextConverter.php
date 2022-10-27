<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Data converter that prepares discount context based on checkout entity to calculate shipping discounts
 */
class CheckoutShippingDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        /** @var Checkout $sourceEntity */
        if (!$this->supports($sourceEntity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($sourceEntity))
            );
        }

        $discountContext = new DiscountContext();
        $discountContext->setSubtotal(0.0);

        $shippingCost = $sourceEntity->getShippingCost();
        if ($shippingCost instanceof Price) {
            $discountContext->setShippingCost($shippingCost->getValue());
            $discountContext->setSubtotal($shippingCost->getValue());
        }

        return $discountContext;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof Checkout && !$sourceEntity->getSourceEntity() instanceof QuoteDemand;
    }
}
