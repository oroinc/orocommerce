<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilderInterface;

/**
 * Defines the contract for factories that create Time In Transit request builders.
 *
 * Implementations of this interface create pre-configured {@see TimeInTransitRequestBuilderInterface} instances
 * with shipment origin, destination, and pickup date already set. This factory pattern simplifies
 * the creation of TNT requests by encapsulating the common initialization logic.
 */
interface TimeInTransitRequestBuilderFactoryInterface
{
    public function createTimeInTransitRequestBuilder(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ): TimeInTransitRequestBuilderInterface;
}
