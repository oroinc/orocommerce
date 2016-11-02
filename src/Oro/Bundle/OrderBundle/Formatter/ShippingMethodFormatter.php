<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class ShippingMethodFormatter
{

    /**
     * @var null|ShippingMethodLabelFormatter
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @param ShippingMethodLabelFormatter|null $shippingMethodLabelFormatter
     */
    public function __construct($shippingMethodLabelFormatter = null)
    {
        $this->shippingMethodLabelFormatter = $shippingMethodLabelFormatter;
    }


    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     * @return array|null
     */
    public function formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
    {
        if ($this->shippingMethodLabelFormatter  !== null) {
            return $this->shippingMethodLabelFormatter->formatShippingMethodWithType(
                $shippingMethodName,
                $shippingTypeName
            );
        }
        return implode(', ', array_filter([$shippingMethodName, $shippingTypeName]));
    }

}
