<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;

class PaymentTermConfig
{
    const PAYMENT_TERM_LABEL_KEY = 'payment_term_label';
    const PAYMENT_TERM_SHORT_LABEL_KEY = 'payment_term_short_label';

    const PAYMENT_TERM_LABEL = 'Payment Terms';

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
        return OroPaymentTermExtension::ALIAS;
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::PAYMENT_TERM_LABEL_KEY,
            self::PAYMENT_TERM_LABEL
        );
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getShortLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            self::PAYMENT_TERM_SHORT_LABEL_KEY,
            self::PAYMENT_TERM_LABEL
        );
    }

    /**
     * @return bool
     */
    public function isAllRequiredFieldsSet()
    {
        $fields = [
            $this->getLabel(),
            $this->getShortLabel(),
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
        return OroPaymentTermExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
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
