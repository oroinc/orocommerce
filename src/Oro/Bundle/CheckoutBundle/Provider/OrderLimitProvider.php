<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

/**
 * Provider for checking if minimum and maximum order amount are met for starting order from shopping list and
 * starting order from re order
 */
class OrderLimitProvider implements OrderLimitProviderInterface
{
    public function __construct(
        private OrderLimitConfigProvider $orderLimitConfigProvider,
        private SubtotalProviderInterface $subtotalProvider,
        private UserCurrencyManager $userCurrencyManager
    ) {
    }

    #[\Override]
    public function isMinimumOrderAmountMet(LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity): bool
    {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        if (null === $currentCurrency) {
            return true;
        }

        $minimumOrderAmount = $this->orderLimitConfigProvider->getMinimumOrderAmount($currentCurrency);
        if (empty($minimumOrderAmount)) {
            return true;
        }

        $orderAmount = $this->subtotalProvider->getSubtotal($entity)->getAmount();

        return $orderAmount > $minimumOrderAmount;
    }

    #[\Override]
    public function isMaximumOrderAmountMet(LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity): bool
    {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        if (null === $currentCurrency) {
            return true;
        }

        $maximumOrderAmount = $this->orderLimitConfigProvider->getMaximumOrderAmount($currentCurrency);
        if (empty($maximumOrderAmount)) {
            return true;
        }

        $orderAmount = $this->subtotalProvider->getSubtotal($entity)->getAmount();

        return $orderAmount < $maximumOrderAmount;
    }
}
