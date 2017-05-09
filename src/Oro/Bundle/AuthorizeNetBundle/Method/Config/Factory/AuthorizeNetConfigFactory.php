<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class AuthorizeNetConfigFactory implements AuthorizeNetConfigFactoryInterface
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
     * @param AuthorizeNetSettings $settings
     *
     * @return AuthorizeNetConfig
     * @throws \InvalidArgumentException
     */
    public function createConfig(AuthorizeNetSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[AuthorizeNetConfig::PAYMENT_METHOD_IDENTIFIER_KEY] = $this->getPaymentMethodIdentifier($channel);
        $params[AuthorizeNetConfig::ADMIN_LABEL_KEY] = $channel->getName();
        $params[AuthorizeNetConfig::LABEL_KEY] = $this->getLocalizedValue($settings->getCreditCardLabels());
        $params[AuthorizeNetConfig::SHORT_LABEL_KEY] = $this->getLocalizedValue($settings->getCreditCardShortLabels());
        $params[AuthorizeNetConfig::ALLOWED_CREDIT_CARD_TYPES_KEY] = $settings->getAllowedCreditCardTypes();
        $params[AuthorizeNetConfig::TEST_MODE_KEY] = $settings->getAuthNetTestMode();
        $params[AuthorizeNetConfig::PURCHASE_ACTION_KEY] = $settings->getCreditCardPaymentAction();
        $params[AuthorizeNetConfig::API_LOGIN_ID] = $this->getDecryptedValue($settings->getApiLoginId());
        $params[AuthorizeNetConfig::TRANSACTION_KEY] = $this->getDecryptedValue($settings->getTransactionKey());
        $params[AuthorizeNetConfig::CLIENT_KEY] = $this->getDecryptedValue($settings->getClientKey());
        $params[AuthorizeNetConfig::REQUIRE_CVV_ENTRY_KEY] = $settings->getAuthNetRequireCVVEntry();

        return new AuthorizeNetConfig($params);
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
