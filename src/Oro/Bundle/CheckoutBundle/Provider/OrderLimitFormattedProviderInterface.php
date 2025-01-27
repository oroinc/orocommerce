<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;

/**
 * Interface for providers returning minimum and maximum order amounts formatted with currency
 */
interface OrderLimitFormattedProviderInterface
{
    public function getMinimumOrderAmountFormatted(): string;

    public function getMinimumOrderAmountDifferenceFormatted(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity
    ): string;

    public function getMaximumOrderAmountFormatted(): string;

    public function getMaximumOrderAmountDifferenceFormatted(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity
    ): string;
}
