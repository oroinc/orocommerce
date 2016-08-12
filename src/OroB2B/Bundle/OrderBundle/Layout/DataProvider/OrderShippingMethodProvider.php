<?php

namespace OroB2B\Bundle\OrderBundle\Layout\DataProvider;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

use Oro\Component\Layout\ContextInterface;

class OrderShippingMethodProvider
{
    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @param ShippingMethodLabelFormatter $shippingMethodLabelFormatter
     */
    public function __construct(ShippingMethodLabelFormatter $shippingMethodLabelFormatter)
    {
        $this->shippingMethodLabelFormatter = $shippingMethodLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Order $order */
        $order = $context->data()->get('order');
        $methodLabel = $this->shippingMethodLabelFormatter->formatShippingMethodLabel($order->getShippingMethod());
        if (!$methodLabel) {
            return false;
        }
        $methodTypeLabel = $this->shippingMethodLabelFormatter->formatShippingMethodTypeLabel(
            $order->getShippingMethod(),
            $order->getShippingMethodType()
        );
        if (!$methodTypeLabel) {
            return $methodLabel;
        }

        return sprintf('%s, %s', $methodLabel, $methodTypeLabel);
    }
}
