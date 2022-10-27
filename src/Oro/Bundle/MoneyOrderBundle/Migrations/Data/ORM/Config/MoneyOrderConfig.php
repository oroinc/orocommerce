<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * The config to move money order settings.
 */
class MoneyOrderConfig
{
    public const MONEY_ORDER_LABEL_KEY = 'money_order_label';
    public const MONEY_ORDER_SHORT_LABEL_KEY = 'money_order_short_label';
    public const MONEY_ORDER_PAY_TO_KEY = 'money_order_pay_to';
    public const MONEY_ORDER_SEND_TO_KEY = 'money_order_send_to';

    private const MONEY_ORDER_LABEL = 'Check/Money Order';

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getLabel(): LocalizedFallbackValue
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::MONEY_ORDER_LABEL_KEY,
            self::MONEY_ORDER_LABEL
        );
    }

    public function getShortLabel(): LocalizedFallbackValue
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::MONEY_ORDER_SHORT_LABEL_KEY,
            self::MONEY_ORDER_LABEL
        );
    }

    public function getPayTo(): string
    {
        return (string)$this->getConfigValue(self::MONEY_ORDER_PAY_TO_KEY);
    }

    public function getSendTo(): string
    {
        return (string)$this->getConfigValue(self::MONEY_ORDER_SEND_TO_KEY);
    }

    public function isAllRequiredFieldsSet(): bool
    {
        if (!$this->getLabel()) {
            return false;
        }
        if (!$this->getShortLabel()) {
            return false;
        }
        if (!$this->getPayTo()) {
            return false;
        }
        if (!$this->getSendTo()) {
            return false;
        }

        return true;
    }

    private function getConfigValue(string $key): mixed
    {
        return $this->configManager->get('oro_money_order' . ConfigManager::SECTION_MODEL_SEPARATOR . $key);
    }

    private function getLocalizedFallbackValueFromConfig(string $key, string $default): LocalizedFallbackValue
    {
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString($this->getConfigValue($key) ?: $default);

        return $localizedFallbackValue;
    }
}
