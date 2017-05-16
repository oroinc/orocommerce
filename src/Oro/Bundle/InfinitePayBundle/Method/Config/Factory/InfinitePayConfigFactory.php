<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\InfinitePayBundle\Entity\InfinitePaySettings;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfig;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class InfinitePayConfigFactory implements InfinitePayConfigFactoryInterface
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
     * @inheritDoc
     */
    public function createConfig(InfinitePaySettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[InfinitePayConfig::FIELD_PAYMENT_METHOD_IDENTIFIER] = $this->getPaymentMethodIdentifier($channel);
        $params[InfinitePayConfig::FIELD_LABEL] = $this->getLocalizedValue($settings->getInfinitePayLabels());
        $params[InfinitePayConfig::FIELD_SHORT_LABEL] =
            $this->getLocalizedValue($settings->getInfinitePayShortLabels());
        $params[InfinitePayConfig::FIELD_ADMIN_LABEL] = $channel->getName();

        $params[InfinitePayConfig::CLIENT_REF_KEY] = $settings->getInfinitePayClientRef();
        $params[InfinitePayConfig::USERNAME_KEY] = $settings->getInfinitePayUsername();
        $params[InfinitePayConfig::PASSWORD_KEY] = $this->getDecryptedValue($settings->getInfinitePayPassword());
        $params[InfinitePayConfig::SECRET_KEY] = $this->getDecryptedValue($settings->getInfinitePaySecret());
        $params[InfinitePayConfig::AUTO_CAPTURE_KEY] = $settings->isInfinitePayAutoCapture();
        $params[InfinitePayConfig::AUTO_ACTIVATE_KEY] = $settings->isInfinitePayAutoActivate();
        $params[InfinitePayConfig::TEST_MODE_KEY] = $settings->isInfinitePayTestMode();
        $params[InfinitePayConfig::DEBUG_MODE_KEY] = $settings->isInfinitePayDebugMode();
        $params[InfinitePayConfig::INVOICE_DUE_PERIOD_KEY] = $settings->getInfinitePayInvoiceDuePeriod();
        $params[InfinitePayConfig::INVOICE_SHIPPING_DURATION_KEY] = $settings->getInfinitePayInvoiceShippingDuration();

        return new InfinitePayConfig($params);
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
