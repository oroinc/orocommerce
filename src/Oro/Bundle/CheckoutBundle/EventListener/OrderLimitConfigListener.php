<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;

/**
 * Formats order limits settings values for enabled currencies
 * Also adds empty values for enabled currencies for which there are no records in settings yet
 */
class OrderLimitConfigListener
{
    private CurrencyProviderInterface $currencyProvider;
    private string $orderLimitConfigName;

    public function __construct(CurrencyProviderInterface $currencyProvider, string $orderLimitConfigName)
    {
        $this->currencyProvider = $currencyProvider;
        $this->orderLimitConfigName = $orderLimitConfigName;
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event): void
    {
        $settingsKey = str_replace(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            ConfigManager::SECTION_VIEW_SEPARATOR,
            $this->orderLimitConfigName
        );
        $settings = $event->getSettings();

        if (!isset($settings[$settingsKey]['value'])) {
            return;
        }

        $settings[$settingsKey]['value'] = $this->getEnabledCurrenciesWithPreFilledData(
            $settings[$settingsKey]['value']
        );

        $event->setSettings($settings);
    }

    /**
     * @param array<array{currency: string, value: float|null}> $data
     * @return array<array{currency: string, value: float|string}>
     */
    private function getEnabledCurrenciesWithPreFilledData(array $data): array
    {
        $keyedData = [];
        foreach ($data as $item) {
            $keyedData[$item['currency']] = $item['value'];
        }

        $enabledCurrencies = $this->currencyProvider->getCurrencyList();

        foreach ($enabledCurrencies as &$currency) {
            $currency = [
                'value' => $keyedData[$currency] ?? null,
                'currency' => $currency
            ];
        }

        return $enabledCurrencies;
    }
}
