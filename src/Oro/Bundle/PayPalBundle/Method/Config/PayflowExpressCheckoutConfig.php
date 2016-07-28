<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;

class PayflowExpressCheckoutConfig extends AbstractPaymentConfig implements PayflowExpressCheckoutConfigInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentExtensionAlias()
    {
        return OroPayPalExtension::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return [
            Option\Partner::PARTNER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
            Option\Vendor::VENDOR => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_USER_KEY),
            Option\Password::PASSWORD => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_EXPRESS_CHECKOUT_ENABLED_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseAction()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isTestMode()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_TEST_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return (int)$this->getConfigValue(Configuration::PAYFLOW_EXPRESS_CHECKOUT_SORT_ORDER_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_EXPRESS_CHECKOUT_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_EXPRESS_CHECKOUT_SHORT_LABEL_KEY);
    }
}
