<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

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
