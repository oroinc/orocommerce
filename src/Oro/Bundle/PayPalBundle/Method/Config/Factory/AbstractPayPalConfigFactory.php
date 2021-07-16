<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Abstract config factory class PayPal integrations
 */
abstract class AbstractPayPalConfigFactory
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    public function __construct(
        LocalizationHelper $localizationHelper,
        IntegrationIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * @param PayPalSettings $settings
     * @return array
     */
    protected function getCredentials(PayPalSettings $settings)
    {
        return [
            Option\Vendor::VENDOR => $settings->getVendor(),
            Option\User::USER => $settings->getUser(),
            Option\Password::PASSWORD => $settings->getPassword(),
            Option\Partner::PARTNER => $settings->getPartner(),
        ];
    }

    /**
     * @param Collection $values
     * @return string
     */
    protected function getLocalizedValue(Collection $values)
    {
        return (string)$this->localizationHelper->getLocalizedValue($values);
    }

    /**
     * @param Channel $channel
     * @return string
     */
    protected function getPaymentMethodIdentifier(Channel $channel)
    {
        return (string)$this->identifierGenerator->generateIdentifier($channel);
    }
}
