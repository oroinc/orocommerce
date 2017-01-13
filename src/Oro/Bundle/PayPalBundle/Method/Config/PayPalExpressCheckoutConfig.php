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

class PayPalExpressCheckoutConfig extends AbstractPaymentConfig implements
    PayPalExpressCheckoutConfigInterface
{
    const TYPE = 'express_checkout';

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param Channel $channel
     * @param SymmetricCrypterInterface $encoder
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        Channel $channel,
        SymmetricCrypterInterface $encoder,
        LocalizationHelper $localizationHelper
    ) {
        parent::__construct($channel);

        $this->encoder = $encoder;
        $this->localizationHelper = $localizationHelper;
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
            Option\Password::PASSWORD =>
                $this->encoder->decryptData($this->getConfigValue(PayPalSettings::PASSWORD_KEY))
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
        return (string)$this->localizationHelper
            ->getLocalizedValue($this->getConfigValue(PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY));
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->localizationHelper
            ->getLocalizedValue($this->getConfigValue(PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY));
    }

    /** {@inheritdoc} */
    public function getAdminLabel()
    {
        return sprintf('%s (Express Checkout)', $this->channel->getName());
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return $this->channel->getType() . '_' . self::TYPE . '_' . $this->channel->getId();
    }
}
