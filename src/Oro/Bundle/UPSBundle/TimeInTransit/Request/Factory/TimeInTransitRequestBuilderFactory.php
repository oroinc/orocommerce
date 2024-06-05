<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilder;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilderInterface;

/**
 * Base implementation of UPS TimeInTransit request builder factory
 */
class TimeInTransitRequestBuilderFactory implements TimeInTransitRequestBuilderFactoryInterface
{
    public function __construct(
        private SymmetricCrypterInterface $crypter
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createTimeInTransitRequestBuilder(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ): TimeInTransitRequestBuilderInterface {
        return new TimeInTransitRequestBuilder(
            $transport->getUpsApiUser(),
            $this->crypter->decryptData($transport->getUpsApiPassword()),
            $transport->getUpsApiKey(),
            $transport->getUpsClientId(),
            $this->crypter->decryptData($transport->getUpsClientSecret()),
            $shipFromAddress,
            $shipToAddress,
            $pickupDate
        );
    }
}
