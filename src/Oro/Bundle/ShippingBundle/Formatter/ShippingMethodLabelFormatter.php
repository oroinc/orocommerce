<?php

namespace Oro\Bundle\ShippingBundle\Formatter;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingMethodLabelFormatter
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
     * @return string
     */
    public function formatShippingMethodLabel($shippingMethodName)
    {
        try {
            $shippingMethod = $this->shippingMethodRegistry->getShippingMethod($shippingMethodName);

            return $label = $shippingMethod->getLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     * @return string
     */
    public function formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName)
    {
        try {
            $shippingMethod = $this->shippingMethodRegistry->getShippingMethod($shippingMethodName);

            return $label = $shippingMethod->getShippingTypeLabel($shippingTypeName);
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }
}
