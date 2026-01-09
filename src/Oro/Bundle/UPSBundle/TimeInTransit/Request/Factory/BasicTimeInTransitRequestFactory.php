<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * Creates basic UPS Time In Transit API requests.
 *
 * This factory implementation creates TNT requests with essential parameters including shipment weight,
 * origin and destination addresses, and pickup date.
 * It delegates to a {@see TimeInTransitRequestBuilderFactoryInterface} to create the builder and then
 * configures it with the shipment weight from the transport settings.
 */
class BasicTimeInTransitRequestFactory implements TimeInTransitRequestFactoryInterface
{
    /**
     * @var TimeInTransitRequestBuilderFactoryInterface
     */
    private $timeInTransitRequestBuilderFactory;

    public function __construct(TimeInTransitRequestBuilderFactoryInterface $timeInTransitRequestBuilderFactory)
    {
        $this->timeInTransitRequestBuilderFactory = $timeInTransitRequestBuilderFactory;
    }

    #[\Override]
    public function createRequest(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        int $weight
    ): UpsClientRequestInterface {
        $requestBuilder = $this->timeInTransitRequestBuilderFactory
            ->createTimeInTransitRequestBuilder($transport, $shipFromAddress, $shipToAddress, $pickupDate);

        $requestBuilder->setWeight($weight, $transport->getUpsUnitOfWeight());

        return $requestBuilder->createRequest();
    }
}
