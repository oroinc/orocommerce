<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;

class ShippingTrackingFormatter
{
    /**
     * @var array|null
     */
    protected $shippingTrackingMethods;

    /**
     * @var null|TrackingAwareShippingMethodsProviderInterface
     */
    protected $trackingAwareShippingMethodsProvider;

    /**
     * @param TrackingAwareShippingMethodsProviderInterface|null $trackingAwareShippingMethodsProvider
     */
    public function __construct($trackingAwareShippingMethodsProvider = null)
    {
        $this->trackingAwareShippingMethodsProvider = $trackingAwareShippingMethodsProvider;
    }

    /**
     * @return array|null
     */
    private function getTrackingMethods()
    {
        if ($this->shippingTrackingMethods !== null) {
            return $this->shippingTrackingMethods;
        }

        $this->shippingTrackingMethods = [];
        if ($this->trackingAwareShippingMethodsProvider !== null) {
            $this->shippingTrackingMethods = $this
                ->trackingAwareShippingMethodsProvider
                ->getTrackingAwareShippingMethods();
        }
        return $this->shippingTrackingMethods;
    }

    /**
     * @param string $shippingMethodName
     * @return string
     */
    public function formatShippingTrackingMethod($shippingMethodName)
    {
        if ($this->getTrackingMethods() && array_key_exists($shippingMethodName, $this->getTrackingMethods())) {
            $label = $this->getTrackingMethods()[$shippingMethodName]->getLabel();
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
        if ($this->getTrackingMethods() && array_key_exists($shippingMethodName, $this->getTrackingMethods())) {
            $link = $this->getTrackingMethods()[$shippingMethodName]->getTrackingLink($trackingNumber);
            if ($link) {
                return $link;
            }
        }
        return $trackingNumber;
    }
}
