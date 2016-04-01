<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayPalPaymentsPro extends PayflowGateway
{
    /** {@inheritdoc} */
    protected function getCredentials()
    {
        return [
            Option\Vendor::VENDOR => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_USER_KEY),
            Option\Password::PASSWORD => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY),
            Option\Partner::PARTNER => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PARTNER_KEY),
        ];
    }
}
