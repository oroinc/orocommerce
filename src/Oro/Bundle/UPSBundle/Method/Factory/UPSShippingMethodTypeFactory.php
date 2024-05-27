<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

/**
 * Basic implementation of UPS Shipping Method Type Factory
 */
class UPSShippingMethodTypeFactory implements UPSShippingMethodTypeFactoryInterface
{
    public function __construct(
        private UPSMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        private IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator,
        private UPSTransport $transport,
        private PriceRequestFactory $priceRequestFactory,
        private ShippingPriceCache $shippingPriceCache
    ) {
    }

    /**
     * @param Channel $channel
     * @param ShippingService $service
     * @return UPSShippingMethodType
     */
    public function create(Channel $channel, ShippingService $service)
    {
        return new UPSShippingMethodType(
            $this->getIdentifier($channel, $service),
            $this->getLabel($service),
            $this->integrationIdentifierGenerator->generateIdentifier($channel),
            $service,
            $this->getSettings($channel),
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache
        );
    }

    /**
     * @param Channel $channel
     * @param ShippingService $service
     * @return string
     */
    private function getIdentifier(Channel $channel, ShippingService $service)
    {
        return $this->typeIdentifierGenerator->generateIdentifier($channel, $service);
    }

    /**
     * @param ShippingService $service
     * @return string
     */
    private function getLabel(ShippingService $service)
    {
        return $service->getDescription();
    }

    /**
     * @param Channel $channel
     * @return \Oro\Bundle\IntegrationBundle\Entity\Transport|UPSSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }
}
