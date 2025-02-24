<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Brick\Math\BigDecimal;
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

        $minimumOrderAmount = BigDecimal::of($minimumOrderAmount);
        $orderAmount = BigDecimal::of(
            $this->subtotalProvider->getSubtotal($entity)->getAmount()
        );

        return $orderAmount->isGreaterThanOrEqualTo($minimumOrderAmount);
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

        $maximumOrderAmount = BigDecimal::of($maximumOrderAmount);
        $orderAmount = BigDecimal::of(
            $this->subtotalProvider->getSubtotal($entity)->getAmount()
        );

        return $orderAmount->isLessThanOrEqualTo($maximumOrderAmount);
    }
}
