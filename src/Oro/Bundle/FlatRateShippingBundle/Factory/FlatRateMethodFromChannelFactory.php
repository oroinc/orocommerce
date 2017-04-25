<?php

namespace Oro\Bundle\FlatRateShippingBundle\Factory;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class FlatRateMethodFromChannelFactory implements IntegrationShippingMethodFactoryInterface
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIconProviderInterface
     */
    private $integrationIconProvider;

    /**
     * @param IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
     * @param LocalizationHelper $localizationHelper
     * @param IntegrationIconProviderInterface $integrationIconProvider
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper,
        IntegrationIconProviderInterface $integrationIconProvider
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->integrationIconProvider = $integrationIconProvider;
    }

    /**
     * @param Channel $channel
     *
     * @return FlatRateMethod
     */
    public function create(Channel $channel)
    {
        $id = $this->identifierGenerator->generateIdentifier($channel);
        $label = $this->getChannelLabel($channel);
        $icon = $this->getIcon($channel);

        return new FlatRateMethod($id, $label, $icon, $channel->isEnabled());
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getChannelLabel(Channel $channel)
    {
        /** @var FlatRateSettings $transport */
        $transport = $channel->getTransport();

        return (string) $this->localizationHelper->getLocalizedValue($transport->getLabels());
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getIcon(Channel $channel)
    {
        return $this->integrationIconProvider->getIcon($channel);
    }
}
