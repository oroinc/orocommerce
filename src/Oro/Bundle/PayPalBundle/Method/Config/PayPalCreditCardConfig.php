<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalCreditCardConfig extends AbstractPaymentConfig implements PayPalCreditCardConfigInterface
{
    const TYPE = 'credit_card';

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
            Option\Vendor::VENDOR => $this->getConfigValue(PayPalSettings::VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(PayPalSettings::USER_KEY),
            Option\Password::PASSWORD =>
                $this->encoder->decryptData($this->getConfigValue(PayPalSettings::PASSWORD_KEY))
            ,
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
        return (string)$this->localizationHelper
            ->getLocalizedValue($this->getConfigValue(PayPalSettings::CREDIT_CARD_LABELS_KEY));
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->localizationHelper
            ->getLocalizedValue($this->getConfigValue(PayPalSettings::CREDIT_CARD_SHORT_LABELS_KEY));
    }

    /** {@inheritdoc} */
    public function getAdminLabel()
    {
        return sprintf('%s (Credit Card)', $this->channel->getName());
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return $this->channel->getType() . '_' . self::TYPE . '_' . $this->channel->getId();
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
