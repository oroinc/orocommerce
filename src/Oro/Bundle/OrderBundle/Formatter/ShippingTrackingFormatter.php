<?php

namespace Oro\Bundle\OrderBundle\Formatter;

class ShippingTrackingFormatter
{
    /**
     * @var array|null
     */
    protected $shippingTrackingMethods;

    /**
     * @var null|object
     */
    private $shippingMethodRegistry;

    /**
     * @param object|null $shippingMethodRegistry
     */
    public function setShippingMethodRegistry($shippingMethodRegistry = null)
    {
        $this->shippingMethodRegistry = $shippingMethodRegistry;
        $this->shippingTrackingMethods = [];
        if ($this->shippingMethodRegistry !== null &&
            get_class($this->shippingMethodRegistry) === 'Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry'
        ) {
            $this->shippingTrackingMethods = $this->shippingMethodRegistry->getTrackingAwareShippingMethods();
        }
    }

    /**
     * @param string $shippingMethodName
     * @return string
     */
    public function formatShippingTrackingMethod($shippingMethodName)
    {
        if ($this->shippingTrackingMethods && array_key_exists($shippingMethodName, $this->shippingTrackingMethods)) {
            $label = $this->shippingTrackingMethods[$shippingMethodName]->getLabel();
            if ($label) {
                return $label;
            }
        }
        return $shippingMethodName;
    }

    /**
     * @param string $shippingMethodName
     * @param string $trackingNumber
     * @return string
     */
    public function formatShippingTrackingLink($shippingMethodName, $trackingNumber)
    {
        if ($this->shippingTrackingMethods && array_key_exists($shippingMethodName, $this->shippingTrackingMethods)) {
            $link = $this->shippingTrackingMethods[$shippingMethodName]->getTrackingLink($trackingNumber);
            if ($link) {
                return $link;
            }
        }
        return $trackingNumber;
    }
}
