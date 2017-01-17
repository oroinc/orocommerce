<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfig implements PayPalExpressCheckoutConfigInterface
{
    const TYPE = 'express_checkout';
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
            Option\Partner::PARTNER => $this->getConfigValue(PayPalSettings::PARTNER_KEY),
            Option\Vendor::VENDOR => $this->getConfigValue(PayPalSettings::VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(PayPalSettings::USER_KEY),
            Option\Password::PASSWORD =>$this->getConfigValue(PayPalSettings::PASSWORD_KEY)
            ,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseAction()
    {
        return (string)$this->getConfigValue(PayPalSettings::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY);
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
    public function getLabel()
    {
        return (string)$this->getConfigValue(PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY);
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
}
