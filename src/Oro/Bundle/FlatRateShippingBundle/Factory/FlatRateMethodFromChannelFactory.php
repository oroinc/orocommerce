<?php

namespace Oro\Bundle\FlatRateShippingBundle\Factory;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
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
     * @param IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
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

        return new FlatRateMethod($id, $label, $channel->isEnabled());
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
}
