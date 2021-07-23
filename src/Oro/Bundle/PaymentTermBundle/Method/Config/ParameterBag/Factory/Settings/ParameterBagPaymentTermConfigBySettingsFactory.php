<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\Factory\Settings;

use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings\PaymentTermConfigBySettingsFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\ParameterBagPaymentTermConfig;

class ParameterBagPaymentTermConfigBySettingsFactory implements PaymentTermConfigBySettingsFactoryInterface
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $integrationIdentifierGenerator;

    public function __construct(
        LocalizationHelper $localizationHelper,
        IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->integrationIdentifierGenerator = $integrationIdentifierGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function createConfigBySettings(PaymentTermSettings $paymentTermSettings)
    {
        $channel = $paymentTermSettings->getChannel();

        $label = (string)$this->localizationHelper->getLocalizedValue($paymentTermSettings->getLabels());
        $adminLabel = $channel->getName();
        $shortLabel = (string)$this->localizationHelper->getLocalizedValue($paymentTermSettings->getShortLabels());
        $paymentMethodIdentifier = $this->integrationIdentifierGenerator->generateIdentifier($channel);

        return new ParameterBagPaymentTermConfig(
            [
                ParameterBagPaymentTermConfig::FIELD_ADMIN_LABEL => $adminLabel,
                ParameterBagPaymentTermConfig::FIELD_LABEL => $label,
                ParameterBagPaymentTermConfig::FIELD_SHORT_LABEL => $shortLabel,
                ParameterBagPaymentTermConfig::FIELD_PAYMENT_METHOD_IDENTIFIER => $paymentMethodIdentifier
            ]
        );
    }
}
