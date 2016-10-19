<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class OrderShippingMethodProvider
{
    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @param ShippingMethodLabelFormatter $shippingMethodLabelFormatter|null
     */
    public function __construct(ShippingMethodLabelFormatter $shippingMethodLabelFormatter = null)
    {
        $this->shippingMethodLabelFormatter = $shippingMethodLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(Order $order)
    {
        if ($this->shippingMethodLabelFormatter) {
            return $this->shippingMethodLabelFormatter->formatShippingMethodWithType(
                $order->getShippingMethod(),
                $order->getShippingMethodType()
            );
        } else {
            $shipping[] = $order->getShippingMethod();
            $shipping[] = $order->getShippingMethodType();
            return implode(' ', $shipping);
        }
    }
}
