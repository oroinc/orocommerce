<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * Must be implemented by factories which creates request object to get tnt (Time In Transit) data from UPS
 */
interface TimeInTransitRequestFactoryInterface
{
    /**
     * @param UPSTransport     $transport
     * @param AddressInterface $shipFromAddress Origin address
     * @param AddressInterface $shipToAddress Destination address
     * @param \DateTime        $pickupDate Pickup date should be specified in the timezone specific for origin address
     * @param int              $weight Weight in the unit of weight specified in the provided UPSTransport
     *
     * @return UpsClientRequestInterface
     */
    public function createRequest(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        int $weight
    ): UpsClientRequestInterface;
}
