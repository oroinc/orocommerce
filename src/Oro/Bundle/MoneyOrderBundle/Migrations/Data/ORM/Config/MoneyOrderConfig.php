<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;

class MoneyOrderConfig
{
    const MONEY_ORDER_LABEL_KEY = 'money_order_label';
    const MONEY_ORDER_SHORT_LABEL_KEY = 'money_order_short_label';
    const MONEY_ORDER_PAY_TO_KEY = 'money_order_pay_to';
    const MONEY_ORDER_SEND_TO_KEY = 'money_order_send_to';

    const MONEY_ORDER_LABEL = 'Check/Money Order';

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPaymentExtensionAlias()
    {
        return OroMoneyOrderExtension::ALIAS;
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::MONEY_ORDER_LABEL_KEY,
            self::MONEY_ORDER_LABEL
        );
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getShortLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::MONEY_ORDER_SHORT_LABEL_KEY,
            self::MONEY_ORDER_LABEL
        );
    }

    /**
     * @return string
     */
    public function getPayTo()
    {
        return (string)$this->getConfigValue(self::MONEY_ORDER_PAY_TO_KEY);
    }

    /**
     * @return string
     */
    public function getSendTo()
    {
        return (string)$this->getConfigValue(self::MONEY_ORDER_SEND_TO_KEY);
    }

    /**
     * @return bool
     */
    public function isAllRequiredFieldsSet()
    {
        $fields = [
            $this->getLabel(),
            $this->getShortLabel(),
            $this->getPayTo(),
            $this->getSendTo(),
        ];

        foreach ($fields as $field) {
            if (empty($field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getConfigValue($key)
    {
        return $this->configManager->get($this->getFullConfigKey($key));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getFullConfigKey($key)
    {
        return OroMoneyOrderExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return LocalizedFallbackValue
     */
    private function getLocalizedFallbackValueFromConfig($key, $default)
    {
        $creditCardLabel = $this->getConfigValue($key);

        return (new LocalizedFallbackValue())->setString($creditCardLabel ?: $default);
    }
}
