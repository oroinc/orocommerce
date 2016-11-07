<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter;

class OrderShippingMethodProvider
{
    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $shippingMethodFormatter;

    /**
     * @param ShippingMethodFormatter $shippingMethodFormatter|null
     */
    public function __construct(ShippingMethodFormatter $shippingMethodFormatter = null)
    {
        $this->shippingMethodFormatter = $shippingMethodFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(Order $order)
    {
        return $this->shippingMethodFormatter->formatShippingMethodWithTypeLabel(
            $order->getShippingMethod(),
            $order->getShippingMethodType()
        );
    }
}
