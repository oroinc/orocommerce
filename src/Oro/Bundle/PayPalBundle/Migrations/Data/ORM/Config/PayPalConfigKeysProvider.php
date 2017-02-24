<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class PayPalConfigKeysProvider extends ParameterBag
{
    const LABEL_KEY = 'label';
    const SHORT_LABEL_KEY = 'short_label';
    const ALLOWED_CC_TYPES_KEY = 'allowed_cc_types';
    const PARTNER_KEY = 'partner';
    const USER_KEY = 'user';
    const PASSWORD_KEY = 'password';
    const VENDOR_KEY = 'vendor';
    const PAYMENT_ACTION_KEY = 'payment_action';
    const TEST_MODE_KEY = 'test_mode';
    const USE_PROXY_KEY = 'use_proxy';
    const PROXY_HOST_KEY = 'proxy_host';
    const PROXY_PORT_KEY = 'proxy_port';
    const DEBUG_MODE_KEY = 'debug_mode';
    const ENABLE_SSL_VERIFICATION_KEY = 'enable_ssl_verification';
    const REQUIRE_CVV_KEY = 'require_cvv';
    const ZERO_AMOUNT_AUTHORIZATION_KEY = 'zero_amount_authorization';
    const AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY = 'authorization_for_required_amount';

    const EXPRESS_CHECKOUT_LABEL_KEY = 'express_checkout_label';
    const EXPRESS_CHECKOUT_SHORT_LABEL_KEY = 'express_checkout_short_label';
    const EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY = 'express_checkout_payment_action';

    /**
     * {@inheritDoc}
     */
    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
    }

    /**
     * @return string
     */
    public function getPartnerKey()
    {
        return $this->get(self::PARTNER_KEY);
    }

    /**
     * @return string
     */
    public function getVendorKey()
    {
        return $this->get(self::VENDOR_KEY);
    }

    /**
     * @return string
     */
    public function getUserKey()
    {
        return $this->get(self::USER_KEY);
    }

    /**
     * @return string
     */
    public function getPasswordKey()
    {
        return $this->get(self::PASSWORD_KEY);
    }

    /**
     * @return string
     */
    public function getTestModeKey()
    {
        return $this->get(self::TEST_MODE_KEY);
    }

    /**
     * @return string
     */
    public function getDebugModeKey()
    {
        return $this->get(self::DEBUG_MODE_KEY);
    }

    /**
     * @return string
     */
    public function getRequireCVVEntryKey()
    {
        return $this->get(self::REQUIRE_CVV_KEY);
    }

    /**
     * @return string
     */
    public function getZeroAmountAuthorizationKey()
    {
        return $this->get(self::ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    /**
     * @return string
     */
    public function getAuthorizationForRequiredAmountKey()
    {
        return $this->get(self::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }

    /**
     * @return string
     */
    public function getUseProxyKey()
    {
        return $this->get(self::USE_PROXY_KEY);
    }

    /**
     * @return string
     */
    public function getProxyHostKey()
    {
        return $this->get(self::PROXY_HOST_KEY);
    }

    /**
     * @return string
     */
    public function getProxyPortKey()
    {
        return $this->get(self::PROXY_PORT_KEY);
    }

    /**
     * @return string
     */
    public function getEnableSSLVerificationKey()
    {
        return $this->get(self::ENABLE_SSL_VERIFICATION_KEY);
    }

    /**
     * @return string
     */
    public function getCreditCardLabelKey()
    {
        return $this->get(self::LABEL_KEY);
    }

    /**
     * @return string
     */
    public function getCreditCardShortLabelKey()
    {
        return $this->get(self::SHORT_LABEL_KEY);
    }

    /**
     * @return string
     */
    public function getExpressCheckoutLabelKey()
    {
        return $this->get(self::EXPRESS_CHECKOUT_LABEL_KEY);
    }

    /**
     * @return string
     */
    public function getExpressCheckoutShortLabelKey()
    {
        return $this->get(self::EXPRESS_CHECKOUT_SHORT_LABEL_KEY);
    }

    /**
     * @return string
     */
    public function getAllowedCreditCardTypesKey()
    {
        return $this->get(self::ALLOWED_CC_TYPES_KEY);
    }

    /**
     * @return string
     */
    public function getCreditCardPaymentActionKey()
    {
        return $this->get(self::PAYMENT_ACTION_KEY);
    }

    /**
     * @return string
     */
    public function getExpressCheckoutPaymentActionKey()
    {
        return $this->get(self::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY);
    }
}
