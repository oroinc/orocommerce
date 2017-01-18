<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;

class MoneyOrderConfigFactory implements MoneyOrderConfigFactoryInterface
{
    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var IntegrationMethodIdentifierGeneratorInterface */
    private $identifierGenerator;

    /**
     * @param LocalizationHelper $localizationHelper
     * @param IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * @param Channel $channel
     *
     * @return MoneyOrderConfig
     */
    public function create(Channel $channel)
    {
        /** @var MoneyOrderSettings $settings */
        $settings = $channel->getTransport();

        $label = $this->localizationHelper->getLocalizedValue($settings->getLabels());

        return new MoneyOrderConfig(
            $label,
            $this->localizationHelper->getLocalizedValue($settings->getShortLabels()),
            $label,
            $settings->getPayTo(),
            $settings->getSendTo(),
            $this->identifierGenerator->generateIdentifier($channel)
        );
    }
}
