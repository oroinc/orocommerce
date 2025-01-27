<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;

/**
 * Provider for getting minimum and maximum order amount settings values
 */
class OrderLimitConfigProvider
{
    public function __construct(
        private ConfigManager $configManager,
        private CurrencyProviderInterface $currencyProvider
    ) {
    }

    public function getMinimumOrderAmount(string $currentCurrency, $scopeIdentifier = null): ?float
    {
        return $this->getOrderLimitAmount(
            'oro_checkout.minimum_order_amount',
            $currentCurrency,
            $scopeIdentifier
        );
    }

    public function getMaximumOrderAmount(string $currentCurrency, $scopeIdentifier = null): ?float
    {
        return $this->getOrderLimitAmount(
            'oro_checkout.maximum_order_amount',
            $currentCurrency,
            $scopeIdentifier
        );
    }

    private function getOrderLimitAmount(
        string $configName,
        string $currentCurrency,
        $scopeIdentifier = null
    ): ?float {
        $enabledCurrencies = $this->currencyProvider->getCurrencyList();

        if (!in_array($currentCurrency, $enabledCurrencies, true)) {
            return null;
        }

        $orderLimitAmountConfig = $this->configManager->get($configName, false, false, $scopeIdentifier);

        if (!is_array($orderLimitAmountConfig)) {
            return null;
        }

        $enabledOrderLimitAmountConfig = $this->getEnabledCurrenciesWithPreFilledData(
            $orderLimitAmountConfig,
            $enabledCurrencies
        );

        return $enabledOrderLimitAmountConfig[$currentCurrency] ?? null;
    }

    private function getEnabledCurrenciesWithPreFilledData(array $data, array $enabledCurrencies): array
    {
        $keyedData = [];
        foreach ($data as $item) {
            $keyedData[$item['currency']] = $item['value'];
        }

        return array_intersect_key($keyedData, array_flip($enabledCurrencies));
    }
}
