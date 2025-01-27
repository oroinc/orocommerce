<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;

/**
 * Interface for providers checking if minimum and maximum order amount are met based on different order sources
 */
interface OrderLimitProviderInterface
{
    public function isMinimumOrderAmountMet(LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity): bool;

    public function isMaximumOrderAmountMet(LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity): bool;
}
