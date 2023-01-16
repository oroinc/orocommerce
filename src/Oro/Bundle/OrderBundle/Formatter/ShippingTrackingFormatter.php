<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;

/**
 * Provides a shipping tracking method label and a shipping tracking link.
 */
class ShippingTrackingFormatter
{
    private TrackingAwareShippingMethodsProviderInterface $trackingAwareShippingMethodsProvider;
    private ?array $trackingMethods = null;

    public function __construct(TrackingAwareShippingMethodsProviderInterface $trackingAwareShippingMethodsProvider)
    {
        $this->trackingAwareShippingMethodsProvider = $trackingAwareShippingMethodsProvider;
    }

    public function formatShippingTrackingMethod(string $shippingMethodName): string
    {
        $trackingMethods = $this->getTrackingMethods();
        if (isset($trackingMethods[$shippingMethodName])) {
            $label = $trackingMethods[$shippingMethodName]->getLabel();
            if ($label) {
                return $label;
            }
        }

        return $shippingMethodName;
    }

    public function formatShippingTrackingLink(string $shippingMethodName, string $trackingNumber): string
    {
        $trackingMethods = $this->getTrackingMethods();
        if (isset($trackingMethods[$shippingMethodName])) {
            $link = $trackingMethods[$shippingMethodName]->getTrackingLink($trackingNumber);
            if ($link) {
                return $link;
            }
        }

        return $trackingNumber;
    }

    private function getTrackingMethods(): array
    {
        if (null === $this->trackingMethods) {
            $this->trackingMethods = $this->trackingAwareShippingMethodsProvider->getTrackingAwareShippingMethods();
        }

        return $this->trackingMethods;
    }
}
