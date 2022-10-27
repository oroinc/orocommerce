<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;

class MoneyOrderConfigFactory implements MoneyOrderConfigFactoryInterface
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
     * {@inheritDoc}
     */
    public function create(MoneyOrderSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[MoneyOrderConfig::LABEL_KEY] = $this->getLocalizedValue($settings->getLabels());
        $params[MoneyOrderConfig::SHORT_LABEL_KEY] = $this->getLocalizedValue($settings->getShortLabels());
        $params[MoneyOrderConfig::ADMIN_LABEL_KEY] = $channel->getName();
        $params[MoneyOrderConfig::PAYMENT_METHOD_IDENTIFIER_KEY] =
            $this->identifierGenerator->generateIdentifier($channel);
        $params[MoneyOrderConfig::PAY_TO_KEY] = $settings->getPayTo();
        $params[MoneyOrderConfig::SEND_TO_KEY] = $settings->getSendTo();

        return new MoneyOrderConfig($params);
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
