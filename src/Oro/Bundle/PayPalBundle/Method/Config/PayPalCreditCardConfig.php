<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

/**
 * Represents PayPal Credit Card payment method configuration.
 *
 * Extends base PayPal configuration with credit card-specific settings including
 * zero-amount authorization, proxy settings, SSL verification, and CVV requirements.
 */
class PayPalCreditCardConfig extends AbstractPayPalConfig implements PayPalCreditCardConfigInterface
{
    public const TYPE = 'credit_card';
    public const DEBUG_MODE_KEY = 'debug_mode';
    public const USE_PROXY_KEY = 'use_proxy';
    public const PROXY_HOST_KEY = 'proxy_host';
    public const PROXY_PORT_KEY = 'proxy_port';
    public const ZERO_AMOUNT_AUTHORIZATION_KEY = 'zero_amount_authorization';
    public const AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY = 'authorization_for_required_amount';
    public const ALLOWED_CREDIT_CARD_TYPES_KEY = 'allowed_credit_card_types';
    public const ENABLE_SSL_VERIFICATION_KEY = 'enable_ssl_verification';
    public const REQUIRE_CVV_ENTRY_KEY = 'require_cvv_entry';

    #[\Override]
    public function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->get(self::ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    #[\Override]
    public function isAuthorizationForRequiredAmountEnabled()
    {
        return (bool)$this->get(self::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }

    #[\Override]
    public function getAllowedCreditCards()
    {
        return (array)$this->get(self::ALLOWED_CREDIT_CARD_TYPES_KEY);
    }

    #[\Override]
    public function isDebugModeEnabled()
    {
        return (bool)$this->get(self::DEBUG_MODE_KEY);
    }

    #[\Override]
    public function isUseProxyEnabled()
    {
        return (bool)$this->get(self::USE_PROXY_KEY);
    }

    #[\Override]
    public function getProxyHost()
    {
        return (string)$this->get(self::PROXY_HOST_KEY);
    }

    #[\Override]
    public function getProxyPort()
    {
        return (int)$this->get(self::PROXY_PORT_KEY);
    }

    #[\Override]
    public function isSslVerificationEnabled()
    {
        return (bool)$this->get(self::ENABLE_SSL_VERIFICATION_KEY);
    }

    #[\Override]
    public function isRequireCvvEntryEnabled()
    {
        return (bool)$this->get(self::REQUIRE_CVV_ENTRY_KEY);
    }
}
