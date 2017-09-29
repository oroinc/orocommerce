<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

interface TimeInTransitRequestFactoryInterface
{
    /**
     * @param UPSTransport     $transport
     * @param AddressInterface $shipFromAddress
     * @param AddressInterface $shipToAddress
     * @param \DateTime        $pickupDate
     *
     * @return UpsClientRequestInterface
     */
    public function createRequest(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    );
}
