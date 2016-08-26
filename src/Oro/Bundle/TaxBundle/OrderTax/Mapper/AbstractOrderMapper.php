<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;

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
     * @var string
     */
    protected $className;

    /**
     * @param ContextEventDispatcher $contextEventDispatcher
     * @param TaxationAddressProvider $addressProvider
     * @param string $className
     */
    public function __construct(
        ContextEventDispatcher $contextEventDispatcher,
        TaxationAddressProvider $addressProvider,
        $className
    ) {
        $this->contextEventDispatcher = $contextEventDispatcher;
        $this->addressProvider = $addressProvider;
        $this->className = (string)$className;
    }

    /**
     * @param Order $order
     * @return AbstractAddress Billing, shipping or origin address according to exclusions
     */
    public function getTaxationAddress(Order $order)
    {
        return $this->addressProvider->getTaxationAddress($order->getBillingAddress(), $order->getShippingAddress());
    }

    /**
     * @param Order $order
     * @return AbstractAddress Billing or shipping address
     */
    public function getDestinationAddress(Order $order)
    {
        return $this->addressProvider->getDestinationAddress($order->getBillingAddress(), $order->getShippingAddress());
    }

    /**
     * @param object $mappingObject
     * @return \ArrayObject
     */
    protected function getContext($mappingObject)
    {
        return $this->contextEventDispatcher->dispatch($mappingObject);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessingClassName()
    {
        return $this->className;
    }
}
