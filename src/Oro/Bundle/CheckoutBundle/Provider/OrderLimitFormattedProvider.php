<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

/**
 * Provider for returning minimum and maximum order amounts formatted with currency
 * for starting order from shopping list and starting order from re order
 */
class OrderLimitFormattedProvider implements OrderLimitFormattedProviderInterface
{
    public function __construct(
        private OrderLimitConfigProvider $orderLimitConfigProvider,
        private SubtotalProviderInterface $subtotalProvider,
        private UserCurrencyManager $userCurrencyManager,
        private NumberFormatter $numberFormatter
    ) {
    }

    #[\Override]
    public function getMinimumOrderAmountFormatted(): string
    {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        if (null === $currentCurrency) {
            return '';
        }

        $minimumOrderAmount = $this->orderLimitConfigProvider->getMinimumOrderAmount($currentCurrency);
        if (null === $minimumOrderAmount) {
            return '';
        }

        return $this->numberFormatter->formatCurrency($minimumOrderAmount);
    }

    #[\Override]
    public function getMinimumOrderAmountDifferenceFormatted(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity
    ): string {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        if (null === $currentCurrency) {
            return '';
        }

        $orderAmount = $this->subtotalProvider->getSubtotal($entity)->getAmount();
        $minimumOrderAmount = $this->orderLimitConfigProvider->getMinimumOrderAmount($currentCurrency);

        if ($orderAmount > $minimumOrderAmount) {
            return '';
        }

        return $this->numberFormatter->formatCurrency($minimumOrderAmount - $orderAmount);
    }

    #[\Override]
    public function getMaximumOrderAmountFormatted(): string
    {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        if (null === $currentCurrency) {
            return '';
        }

        $maximumOrderAmount = $this->orderLimitConfigProvider->getMaximumOrderAmount($currentCurrency);
        if (null === $maximumOrderAmount) {
            return '';
        }

        return $this->numberFormatter->formatCurrency($maximumOrderAmount);
    }

    #[\Override]
    public function getMaximumOrderAmountDifferenceFormatted(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $entity
    ): string {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        if (null === $currentCurrency) {
            return '';
        }

        $orderAmount = $this->subtotalProvider->getSubtotal($entity)->getAmount();
        $maximumOrderAmount = $this->orderLimitConfigProvider->getMaximumOrderAmount($currentCurrency);

        if ($maximumOrderAmount > $orderAmount) {
            return '';
        }

        return $this->numberFormatter->formatCurrency($orderAmount - $maximumOrderAmount);
    }
}
