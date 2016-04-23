<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
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
     * @return array
     */
    public function getAllowedCreditCards()
    {
        return (array)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY);
    }
}
