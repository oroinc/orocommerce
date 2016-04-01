<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowGateway implements PaymentMethodInterface
{
    const TYPE = 'PayPalPaymentsPro';

    /**
     * @var array
     */
    protected $actionMapping = [
        self::AUTHORIZE => Option\Transaction::AUTHORIZATION,
        self::CAPTURE => Option\Transaction::DELAYED_CAPTURE,
        self::CHARGE => Option\Transaction::SALE,
        self::AUTHORIZE => Option\Transaction::AUTHORIZATION,
    ];

    /** @var Gateway */
    protected $gateway;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param Gateway $gateway
     * @param ConfigManager $configManager
     */
    public function __construct(Gateway $gateway, ConfigManager $configManager)
    {
        $this->gateway = $gateway;
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function action($actionName, array $options = [])
    {
        $actionName = (string)$actionName;

        if (!array_key_exists($actionName, $this->actionMapping)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown action "%s", known actions are "%s"',
                    $actionName,
                    implode(', ', array_keys($this->actionMapping))
                )
            );
        }

        $options = array_replace($this->getCredentials(), $options);

        return $this->gateway->request($this->actionMapping[$actionName], $options)->getData();
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return [
            Option\Vendor::VENDOR => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_USER_KEY),
            Option\Password::PASSWORD => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY),
            Option\Partner::PARTNER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getConfigValue($key)
    {
        $key = OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }
}
