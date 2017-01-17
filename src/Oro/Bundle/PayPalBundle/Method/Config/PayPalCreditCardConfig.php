<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalCreditCardConfig implements PayPalCreditCardConfigInterface
{
    const TYPE = 'credit_card';
    const ADMIN_LABEL_KEY  = 'admin_label';
    const PAYMENT_METHOD_IDENTIFIER_KEY = 'payment_method_identifier';
    
    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function getConfigValue($key)
    {
        return $this->parameters->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return [
            Option\Vendor::VENDOR => $this->getConfigValue(PayPalSettings::VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(PayPalSettings::USER_KEY),
            Option\Password::PASSWORD =>$this->getConfigValue(PayPalSettings::PASSWORD_KEY),
            Option\Partner::PARTNER => $this->getConfigValue(PayPalSettings::PARTNER_KEY),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isTestMode()
    {
        return (bool)$this->getConfigValue(PayPalSettings::TEST_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseAction()
    {
        return (string)$this->getConfigValue(PayPalSettings::CREDIT_CARD_PAYMENT_ACTION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(PayPalSettings::ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorizationForRequiredAmountEnabled()
    {
        return (bool)$this->getConfigValue(PayPalSettings::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return (string)$this->getConfigValue(PayPalSettings::CREDIT_CARD_LABELS_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(PayPalSettings::CREDIT_CARD_SHORT_LABELS_KEY);
    }

    /** {@inheritdoc} */
    public function getAdminLabel()
    {
        return (string)$this->getConfigValue(self::ADMIN_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return (string)$this->getConfigValue(self::PAYMENT_METHOD_IDENTIFIER_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCreditCards()
    {
        return (array)$this->getConfigValue(PayPalSettings::ALLOWED_CREDIT_CARD_TYPES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugModeEnabled()
    {
        return (bool)$this->getConfigValue(PayPalSettings::DEBUG_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isUseProxyEnabled()
    {
        return (bool)$this->getConfigValue(PayPalSettings::USE_PROXY_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyHost()
    {
        return (string)$this->getConfigValue(PayPalSettings::PROXY_HOST_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyPort()
    {
        return (int)$this->getConfigValue(PayPalSettings::PROXY_PORT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isSslVerificationEnabled()
    {
        return (bool)$this->getConfigValue(PayPalSettings::ENABLE_SSL_VERIFICATION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isRequireCvvEntryEnabled()
    {
        return (bool)$this->getConfigValue(PayPalSettings::REQUIRE_CVV_ENTRY_KEY);
    }
}
