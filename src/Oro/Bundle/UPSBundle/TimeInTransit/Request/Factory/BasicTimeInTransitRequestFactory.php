<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

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

    /**
     * {@inheritDoc}
     */
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
