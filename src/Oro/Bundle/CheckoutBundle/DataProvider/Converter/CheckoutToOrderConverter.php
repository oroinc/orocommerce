<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class CheckoutToOrderConverter
{
    /** @var array */
    private $orderCache = [];

    /**
     * @var CheckoutLineItemsManager
     */
    private $checkoutLineItemsManager;

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param MapperInterface $mapper
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        MapperInterface $mapper
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->mapper = $mapper;
    }

    /**
     * @param Checkout $checkout
     * @return Order
     */
    public function getOrder(Checkout $checkout)
    {
        $hash = md5(serialize($checkout));
        if (isset($this->orderCache[$hash])) {
            return $this->orderCache[$hash];
        }

        $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];

        $this->orderCache[$hash] = $this->mapper->map($checkout, $data);

        return $this->orderCache[$hash];
    }
}
