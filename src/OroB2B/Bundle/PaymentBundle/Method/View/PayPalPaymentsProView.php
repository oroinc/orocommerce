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
        return (string)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_SHORT_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getAllowedCreditCards()
    {
        return (array)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY);
    }

    /** {@inheritdoc} */
    protected function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    /** {@inheritdoc} */
    protected function isRequireCvvEntryEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_REQUIRE_CVV_KEY);
    }
}
