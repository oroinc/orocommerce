<?php

namespace Oro\Bundle\DPDBundle\Handler;

use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderShippingDPDHandler
{
    /**
     * DPDShippingMethodProvider
     */
    protected $shippingMethodProvider;

    /**
     * @param DPDShippingMethodProvider $shippingMethodProvider
     */
    public function __construct(
        DPDShippingMethodProvider $shippingMethodProvider
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function process(Order $order)
    {
        $response = null;
        $orderShippingMethod = $order->getShippingMethod();
        if ($this->shippingMethodProvider->hasShippingMethod($orderShippingMethod)) {
            $shippingMethod = $this->shippingMethodProvider->getShippingMethod($orderShippingMethod);
            if ($shippingMethod instanceof DPDShippingMethod) {
                $response = $shippingMethod->setOrder($order);
            }
        }

        $result = [
            'successful' => $response ? $response->isSuccessful() : false,
            'errors' => $response ? $response->getErrorMessagesLong() : []
        ];

        return $result;
    }
}
