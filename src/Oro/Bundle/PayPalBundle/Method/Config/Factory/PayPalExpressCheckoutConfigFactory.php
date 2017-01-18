<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfigFactory implements PayPalConfigFactoryInterface
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
     * @return PayPalExpressCheckoutConfig
     * @throws \InvalidArgumentException
     */
    public function createConfig(PayPalSettings $entity)
    {
        $params = [];
        /** @var ParameterBag $parameterBag */
        $parameterBag = $entity->getSettingsBag();
        $channel = $entity->getChannel();

        $params[PayPalExpressCheckoutConfig::LABEL_KEY] = $this->localizationHelper
            ->getLocalizedValue($parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY));
        $params[PayPalExpressCheckoutConfig::SHORT_LABEL_KEY] = $this->localizationHelper
            ->getLocalizedValue($parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY));
        $params[PayPalExpressCheckoutConfig::ADMIN_LABEL_KEY] =
            $parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_NAME_KEY);
        $params[PayPalExpressCheckoutConfig::CREDENTIALS_KEY] = [
            Option\Vendor::VENDOR => $parameterBag->get(PayPalSettings::VENDOR_KEY),
            Option\User::USER => $parameterBag->get(PayPalSettings::USER_KEY),
            Option\Password::PASSWORD =>$this->encoder->decryptData($parameterBag->get(PayPalSettings::PASSWORD_KEY)),
            Option\Partner::PARTNER => $parameterBag->get(PayPalSettings::PARTNER_KEY),
        ];
        $params[PayPalExpressCheckoutConfig::TEST_MODE_KEY] = $parameterBag->get(PayPalSettings::TEST_MODE_KEY);
        $params[PayPalExpressCheckoutConfig::PURCHASE_ACTION_KEY] =
            $parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY);
        $params[PayPalExpressCheckoutConfig::PAYMENT_METHOD_IDENTIFIER_KEY] = sprintf(
            '%s_%s_%d',
            $channel->getType(),
            PayPalExpressCheckoutConfig::TYPE,
            $channel->getId()
        );

        return new PayPalExpressCheckoutConfig($params);
    }
}
