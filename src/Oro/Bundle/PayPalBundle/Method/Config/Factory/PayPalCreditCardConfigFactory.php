<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalCreditCardConfigFactory implements PayPalConfigFactoryInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param SymmetricCrypterInterface $encoder
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        SymmetricCrypterInterface $encoder,
        LocalizationHelper $localizationHelper
    ) {
        $this->encoder = $encoder;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param PayPalSettings $entity
     * @return PayPalCreditCardConfig
     * @throws \InvalidArgumentException
     */
    public function createConfig(PayPalSettings $entity)
    {
        $params = [];
        /** @var ParameterBag $parameterBag */
        $parameterBag = $entity->getSettingsBag();
        $channel = $entity->getChannel();

        $params[PayPalCreditCardConfig::LABEL_KEY] = $this->localizationHelper
            ->getLocalizedValue($parameterBag->get(PayPalSettings::CREDIT_CARD_LABELS_KEY));
        $params[PayPalCreditCardConfig::SHORT_LABEL_KEY] = $this->localizationHelper
            ->getLocalizedValue($parameterBag->get(PayPalSettings::CREDIT_CARD_SHORT_LABELS_KEY));
        $params[PayPalCreditCardConfig::ADMIN_LABEL_KEY] = $channel->getName();
        $params[PayPalCreditCardConfig::CREDENTIALS_KEY] = [
            Option\Vendor::VENDOR => $parameterBag->get(PayPalSettings::VENDOR_KEY),
            Option\User::USER => $parameterBag->get(PayPalSettings::USER_KEY),
            Option\Password::PASSWORD =>$this->encoder->decryptData($parameterBag->get(PayPalSettings::PASSWORD_KEY)),
            Option\Partner::PARTNER => $parameterBag->get(PayPalSettings::PARTNER_KEY)
        ];
        $params[PayPalCreditCardConfig::TEST_MODE_KEY] = $parameterBag->get(PayPalSettings::TEST_MODE_KEY);
        $params[PayPalCreditCardConfig::PURCHASE_ACTION_KEY] =
            $parameterBag->get(PayPalSettings::CREDIT_CARD_PAYMENT_ACTION_KEY);
        $params[PayPalCreditCardConfig::ZERO_AMOUNT_AUTHORIZATION_KEY] =
            $parameterBag->get(PayPalSettings::ZERO_AMOUNT_AUTHORIZATION_KEY);
        $params[PayPalCreditCardConfig::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY] =
            $parameterBag->get(PayPalSettings::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
        $params[PayPalCreditCardConfig::ALLOWED_CREDIT_CARD_TYPES_KEY] =
            $parameterBag->get(PayPalSettings::ALLOWED_CREDIT_CARD_TYPES_KEY);
        $params[PayPalCreditCardConfig::DEBUG_MODE_KEY] = $parameterBag->get(PayPalSettings::DEBUG_MODE_KEY);
        $params[PayPalCreditCardConfig::USE_PROXY_KEY] = $parameterBag->get(PayPalSettings::USE_PROXY_KEY);
        $params[PayPalCreditCardConfig::PROXY_HOST_KEY] = $parameterBag->get(PayPalSettings::PROXY_HOST_KEY);
        $params[PayPalCreditCardConfig::PROXY_PORT_KEY] = $parameterBag->get(PayPalSettings::PROXY_PORT_KEY);
        $params[PayPalCreditCardConfig::ENABLE_SSL_VERIFICATION_KEY] =
            $parameterBag->get(PayPalSettings::ENABLE_SSL_VERIFICATION_KEY);
        $params[PayPalCreditCardConfig::REQUIRE_CVV_ENTRY_KEY] =
            $parameterBag->get(PayPalSettings::REQUIRE_CVV_ENTRY_KEY);
        $params[PayPalCreditCardConfig::PAYMENT_METHOD_IDENTIFIER_KEY] = sprintf(
            '%s_%s_%d',
            $channel->getType(),
            PayPalCreditCardConfig::TYPE,
            $channel->getId()
        );

        return new PayPalCreditCardConfig($params);
    }
}
