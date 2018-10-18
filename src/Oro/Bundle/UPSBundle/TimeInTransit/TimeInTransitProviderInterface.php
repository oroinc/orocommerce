<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

/**
 * Must be implemented by providers which provides tnt (Time In Transit) data from UPS
 */
interface TimeInTransitProviderInterface
{
    /**
     * @param UPSTransport     $transport
     * @param AddressInterface $shipFromAddress Origin address
     * @param AddressInterface $shipToAddress Destination address
     * @param \DateTime        $pickupDate Pickup date should be specified in the timezone specific for origin address
     * @param int              $weight Weight in the unit of weight specified in the provided UPSTransport
     *
     * @return TimeInTransitResultInterface
     */
    public function getTimeInTransitResult(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        int $weight
    ): TimeInTransitResultInterface;
}
