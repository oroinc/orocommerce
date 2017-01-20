<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalExpressCheckoutConfigFactory implements PayPalExpressCheckoutConfigFactoryInterface
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
     * {@inheritdoc}
     */
    public function createConfig(PayPalSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[PayPalExpressCheckoutConfig::PAYMENT_METHOD_IDENTIFIER_KEY] =
            $this->identifierGenerator->generateIdentifier($channel);

        $params[PayPalExpressCheckoutConfig::ADMIN_LABEL_KEY] = $settings->getExpressCheckoutName();
        $params[PayPalExpressCheckoutConfig::LABEL_KEY] =
            $this->getLocalizedValue($settings->getExpressCheckoutLabels());
        $params[PayPalExpressCheckoutConfig::SHORT_LABEL_KEY] =
            $this->getLocalizedValue($settings->getExpressCheckoutShortLabels());

        $params[PayPalExpressCheckoutConfig::CREDENTIALS_KEY] = $this->getCredentials($settings);
        $params[PayPalExpressCheckoutConfig::TEST_MODE_KEY] = $settings->getTestMode();

        $params[PayPalExpressCheckoutConfig::PURCHASE_ACTION_KEY] = $settings->getExpressCheckoutPaymentAction();

        return new PayPalExpressCheckoutConfig($params);
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
