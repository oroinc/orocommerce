<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class FedexShippingMethodTypeFactory implements FedexShippingMethodTypeFactoryInterface
{
    /**
     * @var FedexMethodTypeIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @param FedexMethodTypeIdentifierGeneratorInterface $identifierGenerator
     */
    public function __construct(
        FedexMethodTypeIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel, ShippingService $service): ShippingMethodTypeInterface
    {
        return new FedexShippingMethodType(
            $this->identifierGenerator->generate($service),
            $service->getDescription(),
            $this->getSettings($channel)
        );
    }

    /**
     * @param Channel $channel
     *
     * @return FedexIntegrationSettings|Transport
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }
}
