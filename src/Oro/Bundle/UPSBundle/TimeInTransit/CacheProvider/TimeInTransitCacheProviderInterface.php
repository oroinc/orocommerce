<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

/**
 * Defines the contract for caching UPS Time In Transit (TNT) data.
 *
 * Implementations of this interface provide caching functionality for UPS Time In Transit API responses.
 * TNT data includes estimated delivery dates and transit times for shipments between specific addresses.
 * Caching this data reduces API calls and improves performance when calculating shipping estimates for customers.
 */
interface TimeInTransitCacheProviderInterface
{
    /**
     * @param AddressInterface $shipFromAddress
     * @param AddressInterface $shipToAddress
     * @param \DateTime        $pickupDate
     *
     * @return TimeInTransitResultInterface|null
     */
    public function fetch(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate);

    /**
     * @param AddressInterface $shipFromAddress
     * @param AddressInterface $shipToAddress
     * @param \DateTime        $pickupDate
     *
     * @return bool
     */
    public function contains(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate);

    /**
     * @param AddressInterface             $shipFromAddress
     * @param AddressInterface             $shipToAddress
     * @param \DateTime                    $pickupDate
     * @param TimeInTransitResultInterface $data
     * @param int                          $lifeTime
     *
     * @return bool
     */
    public function save(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        TimeInTransitResultInterface $data,
        $lifeTime = 0
    );

    /**
     * @param AddressInterface $shipFromAddress
     * @param AddressInterface $shipToAddress
     * @param \DateTime        $pickupDate
     *
     * @return bool
     */
    public function delete(AddressInterface $shipFromAddress, AddressInterface $shipToAddress, \DateTime $pickupDate);

    /**
     * @return bool
     */
    public function deleteAll();
}
