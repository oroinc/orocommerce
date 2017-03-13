<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

abstract class AbstractPayPalConfigFactory
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
     * @return array
     */
    protected function getCredentials(PayPalSettings $settings)
    {
        return [
            Option\Vendor::VENDOR => $this->getDecryptedValue($settings->getVendor()),
            Option\User::USER => $this->getDecryptedValue($settings->getUser()),
            Option\Password::PASSWORD => $this->getDecryptedValue($settings->getPassword()),
            Option\Partner::PARTNER => $this->getDecryptedValue($settings->getPartner()),
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
     * @param string $value
     * @return string
     */
    protected function getDecryptedValue($value)
    {
        return (string)$this->encoder->decryptData($value);
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
