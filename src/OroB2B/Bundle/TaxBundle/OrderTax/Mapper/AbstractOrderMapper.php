<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Event\ContextEventDispatcher;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

abstract class AbstractOrderMapper implements TaxMapperInterface
{
    /**
     * @var ContextEventDispatcher
     */
    protected $contextEventDispatcher;

    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    /**
     * @param ContextEventDispatcher $contextEventDispatcher
     * @param TaxationAddressProvider $addressProvider
     */
    public function __construct(
        ContextEventDispatcher $contextEventDispatcher,
        TaxationAddressProvider $addressProvider
    ) {
        $this->contextEventDispatcher = $contextEventDispatcher;
        $this->addressProvider = $addressProvider;
    }

    /**
     * @param Order $order
     * @return AbstractAddress
     */
    public function getOrderAddress(Order $order)
    {
        return $this->addressProvider->getAddressForTaxation($order->getBillingAddress(), $order->getShippingAddress());
    }

    /**
     * @param object $mappingObject
     * @return \ArrayObject
     */
    protected function getContext($mappingObject)
    {
        return $this->contextEventDispatcher->dispatch($mappingObject);
    }
}
