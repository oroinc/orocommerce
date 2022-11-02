<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * The config to move payment term settings.
 */
class PaymentTermConfig
{
    public const PAYMENT_TERM_LABEL_KEY = 'payment_term_label';
    public const PAYMENT_TERM_SHORT_LABEL_KEY = 'payment_term_short_label';

    private const PAYMENT_TERM_LABEL = 'Payment Terms';

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getLabel(): LocalizedFallbackValue
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::PAYMENT_TERM_LABEL_KEY,
            self::PAYMENT_TERM_LABEL
        );
    }

    public function getShortLabel(): LocalizedFallbackValue
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::PAYMENT_TERM_SHORT_LABEL_KEY,
            self::PAYMENT_TERM_LABEL
        );
    }

    public function isAllRequiredFieldsSet(): bool
    {
        if (!$this->getLabel()) {
            return false;
        }
        if (!$this->getShortLabel()) {
            return false;
        }

        return true;
    }

    private function getConfigValue(string $key):? bool
    {
        return $this->configManager->get('oro_payment_term' . ConfigManager::SECTION_MODEL_SEPARATOR . $key);
    }

    private function getLocalizedFallbackValueFromConfig(string $key, string $default): LocalizedFallbackValue
    {
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString($this->getConfigValue($key) ?: $default);

        return $localizedFallbackValue;
    }
}
