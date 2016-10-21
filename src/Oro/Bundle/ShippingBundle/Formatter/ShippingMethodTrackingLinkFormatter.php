<?php

namespace Oro\Bundle\ShippingBundle\Formatter;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;

class ShippingMethodTrackingLinkFormatter
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $shippingMethodRegistry;

    /**
     * @param ShippingMethodRegistry $shippingMethodRegistry
     */
    public function __construct(ShippingMethodRegistry $shippingMethodRegistry)
    {
        $this->shippingMethodRegistry = $shippingMethodRegistry;
    }


    /**
     * @param string $shippingMethodName
     * @param string $trackingNumber
     * @return string
     */
    public function formatShippingTrackingLink($shippingMethodName, $trackingNumber)
    {
        $shippingMethod = $this->shippingMethodRegistry->getShippingMethod($shippingMethodName);

        if (!$shippingMethod || !($shippingMethod instanceof ShippingTrackingAwareInterface)) {
            return $trackingNumber;
        }
        $link = $shippingMethod->getTrackingLink($trackingNumber);
        if ($link) {
            return "<a target='_blank' href='" . $link . "'>" . $trackingNumber . '</a>';
        } else {
            return $trackingNumber;
        }
    }
}
