<?php

namespace Oro\Bundle\DPDBundle\Factory;

use Oro\Bundle\DPDBundle\Context\DPDShippingContextInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;

class DPDShippingContextFactory
{
    /**
     * @var OrderShippingContextFactory
     */
    protected $baseShippingContextFactory;

    public function __construct(
        OrderShippingContextFactory $baseShippingContextFactory
    ) {
        $this->baseShippingContextFactory = $baseShippingContextFactory;
    }

    /**
     * @param Order $order
     * @return DPDShippingContextInterface
     */
    public function create(Order $order)
    {
        $baseShippingContext = $this->baseShippingContextFactory->create($order);
        if (!$baseShippingContext) {
            return null;
        }

    }

}