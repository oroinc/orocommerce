<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
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

    public function create(Channel $channel, ShippingService $service): UPSShippingMethodType
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

    private function getIdentifier(Channel $channel, ShippingService $service): string
    {
        return $this->typeIdentifierGenerator->generateIdentifier($channel, $service);
    }

    private function getLabel(ShippingService $service): string
    {
        return $service->getDescription();
    }

    private function getSettings(Channel $channel): UPSSettings|Transport
    {
        return $channel->getTransport();
    }
}
