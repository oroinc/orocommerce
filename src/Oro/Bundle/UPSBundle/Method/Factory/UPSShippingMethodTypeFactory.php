<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSShippingMethodTypeFactory implements UPSShippingMethodTypeFactoryInterface
{
    /**
     * @var UPSMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var UPSTransport
     */
    private $transport;

    /**
     * @var PriceRequestFactory
     */
    private $priceRequestFactory;

    /**
     * @var ShippingPriceCache
     */
    private $shippingPriceCache;

    /**
     * @param UPSMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param UPSTransport $transport
     * @param PriceRequestFactory $priceRequestFactory
     * @param ShippingPriceCache $shippingPriceCache
     */
    public function __construct(
        UPSMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        UPSTransport $transport,
        PriceRequestFactory $priceRequestFactory,
        ShippingPriceCache $shippingPriceCache
    ) {
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->transport = $transport;
        $this->priceRequestFactory = $priceRequestFactory;
        $this->shippingPriceCache = $shippingPriceCache;
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
            $this->methodIdentifierGenerator->generateIdentifier($channel),
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
