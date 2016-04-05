<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;

class PayPalPaymentsProView extends PayflowGatewayView
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return (int)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_SORT_ORDER_KEY);
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PayPalPaymentsPro::TYPE;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_LABEL_KEY);
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
}
