<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalCreditCardConfigFactory implements PayPalCreditCardConfigFactoryInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $encoder;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @param SymmetricCrypterInterface $encoder
     * @param LocalizationHelper $localizationHelper
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     */
    public function __construct(
        SymmetricCrypterInterface $encoder,
        LocalizationHelper $localizationHelper,
        IntegrationIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->encoder = $encoder;
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * @param PayPalSettings $settings
     * @return PayPalCreditCardConfig
     * @throws \InvalidArgumentException
     */
    public function createConfig(PayPalSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[PayPalCreditCardConfig::PAYMENT_METHOD_IDENTIFIER_KEY] = $this->identifierGenerator
            ->generateIdentifier($channel);

        $params[PayPalCreditCardConfig::ADMIN_LABEL_KEY] = $channel->getName();
        $params[PayPalCreditCardConfig::LABEL_KEY] = $this->getLocalizedValue($settings->getCreditCardLabels());
        $params[PayPalCreditCardConfig::SHORT_LABEL_KEY] =
            $this->getLocalizedValue($settings->getCreditCardShortLabels());
        $params[PayPalCreditCardConfig::ALLOWED_CREDIT_CARD_TYPES_KEY] =
            $settings->getAllowedCreditCardTypes()->toArray();

        $params[PayPalCreditCardConfig::CREDENTIALS_KEY] = $this->getCredentials($settings);
        $params[PayPalCreditCardConfig::TEST_MODE_KEY] = $settings->getTestMode();

        $params[PayPalCreditCardConfig::PURCHASE_ACTION_KEY] = $settings->getCreditCardPaymentAction();
        $params[PayPalCreditCardConfig::DEBUG_MODE_KEY] = $settings->getDebugMode();
        $params[PayPalCreditCardConfig::REQUIRE_CVV_ENTRY_KEY] = $settings->getRequireCVVEntry();
        $params[PayPalCreditCardConfig::ZERO_AMOUNT_AUTHORIZATION_KEY] = $settings->getZeroAmountAuthorization();
        $params[PayPalCreditCardConfig::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY] =
            $settings->getAuthorizationForRequiredAmount();

        $params[PayPalCreditCardConfig::USE_PROXY_KEY] = $settings->getUseProxy();
        $params[PayPalCreditCardConfig::PROXY_HOST_KEY] = $settings->getProxyHost();
        $params[PayPalCreditCardConfig::PROXY_PORT_KEY] = $settings->getProxyPort();
        $params[PayPalCreditCardConfig::ENABLE_SSL_VERIFICATION_KEY] = $settings->getEnableSSLVerification();

        return new PayPalCreditCardConfig($params);
    }

    /**
     * @param PayPalSettings $settings
     * @return array
     */
    private function getCredentials(PayPalSettings $settings)
    {
        return [
            Option\Vendor::VENDOR => $settings->getVendor(),
            Option\User::USER => $settings->getUser(),
            Option\Password::PASSWORD => $this->encoder->decryptData($settings->getPassword()),
            Option\Partner::PARTNER => $settings->getPartner(),
        ];
    }

    /**
     * @param Collection $values
     * @return string
     */
    private function getLocalizedValue(Collection $values)
    {
        return (string)$this->localizationHelper->getLocalizedValue($values);
    }
}
