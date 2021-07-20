<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

class CacheableTimeInTransitProvider implements TimeInTransitProviderInterface
{
    const CACHE_LIFETIME = 86400;
    const PICKUP_DATE_CACHE_KEY_FORMAT = 'YmdHi';

    /**
     * @var TimeInTransitProviderInterface
     */
    protected $timeInTransit;

    /**
     * @var TimeInTransitCacheProviderFactoryInterface
     */
    protected $timeInTransitCacheProviderFactory;

    public function __construct(
        TimeInTransitProviderInterface $timeInTransit,
        TimeInTransitCacheProviderFactoryInterface $timeInTransitCacheProviderFactory
    ) {
        $this->timeInTransit = $timeInTransit;
        $this->timeInTransitCacheProviderFactory = $timeInTransitCacheProviderFactory;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * @param UPSTransport $transport
     *
     * @return TimeInTransitCacheProviderInterface
     */
    protected function createCacheProvider(UPSTransport $transport)
    {
        return $this->timeInTransitCacheProviderFactory->createCacheProviderForTransport($transport);
    }
}
