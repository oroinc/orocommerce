<?php

namespace Oro\Bundle\ApruveBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class ApruveConfigFactory implements ApruveConfigFactoryInterface
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @param LocalizationHelper                      $localizationHelper
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        IntegrationIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ApruveSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[ApruveConfig::PAYMENT_METHOD_IDENTIFIER_KEY] =
            $this->identifierGenerator->generateIdentifier($channel);

        $params[ApruveConfig::ADMIN_LABEL_KEY] = $channel->getName();
        $params[ApruveConfig::LABEL_KEY] = $this->getLocalizedValue($settings->getLabels());
        $params[ApruveConfig::SHORT_LABEL_KEY] = $this->getLocalizedValue($settings->getShortLabels());

        $params[ApruveConfig::API_KEY_KEY] = $settings->getApiKey();
        $params[ApruveConfig::MERCHANT_ID_KEY] = $settings->getMerchantId();
        $params[ApruveConfig::TEST_MODE_KEY] = $settings->getTestMode();

        return new ApruveConfig($params);
    }

    /**
     * @param Collection $values
     *
     * @return string
     */
    private function getLocalizedValue(Collection $values)
    {
        return (string)$this->localizationHelper->getLocalizedValue($values);
    }
}
