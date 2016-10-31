<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class ShippingMethodFormatter
{

    /**
     * @var null|ShippingMethodLabelFormatter
     */
    protected $ShippingMethodLabelFormatter;

    /**
     * @param ShippingMethodLabelFormatter|null $shippingMethodLabelFormatter
     */
    public function __construct($shippingMethodLabelFormatter = null)
    {
        $this->ShippingMethodLabelFormatter = $shippingMethodLabelFormatter;
    }

    /**
     * @return array|null
     */
    public function formatShippingMethodLabel($shippingMethodName)
    {
        if ($this->ShippingMethodLabelFormatter  !== null) {
            return $this->ShippingMethodLabelFormatter->formatShippingMethodLabel($shippingMethodName);
        }
        return $shippingMethodName;
    }

    public function formatShippingTypeLabel($shippingTypeName)
    {
        if ($this->ShippingMethodLabelFormatter !== null) {
            return $this->ShippingMethodLabelFormatter->formatShippingTypeLabel($shippingTypeName);
        }
        return $shippingTypeName;
    }
}
