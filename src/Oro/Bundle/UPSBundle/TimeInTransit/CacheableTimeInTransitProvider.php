<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

/**
 * Provider for caching results from Time in Transit UPS API
 */
class CacheableTimeInTransitProvider implements TimeInTransitProviderInterface
{
    private TimeInTransitProviderInterface $timeInTransit;
    private TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory;

    public function __construct(
        TimeInTransitProviderInterface $timeInTransit,
        TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory
    ) {
        $this->timeInTransit = $timeInTransit;
        $this->timeInTransitCacheProviderFactory = $timeInTransitCacheProviderFactory;
    }

    public function getTimeInTransitResult(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        int $weight
    ): TimeInTransitResultInterface {
        $timeInTransitCacheProvider = $this->createCacheProvider($transport);

        if (!$timeInTransitCacheProvider->contains($shipFromAddress, $shipToAddress, $pickupDate)) {
            $result = $this
                ->timeInTransit
                ->getTimeInTransitResult($transport, $shipFromAddress, $shipToAddress, $pickupDate, $weight);

            // Cache only successful results.
            if ($result->getStatus()) {
                $timeInTransitCacheProvider->save($shipFromAddress, $shipToAddress, $pickupDate, $result);
            }
        } else {
            $result = $timeInTransitCacheProvider->fetch($shipFromAddress, $shipToAddress, $pickupDate);
        }

        return $result;
    }

    private function createCacheProvider(UPSTransport $transport) : TimeInTransitCacheProviderInterface
    {
        return $this->timeInTransitCacheProviderFactory->createCacheProviderForTransport($transport);
    }
}
