<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;

/**
 * The base class for services that create {@see \Oro\Bundle\TaxBundle\Model\Taxable} object
 * and fills it with data from a given object.
 */
abstract class AbstractOrderMapper implements TaxMapperInterface
{
    /** @var ContextEventDispatcher */
    protected $contextEventDispatcher;

    /** @var TaxationAddressProvider */
    protected $addressProvider;

    public function __construct(
        ContextEventDispatcher $contextEventDispatcher,
        TaxationAddressProvider $addressProvider
    ) {
        $this->contextEventDispatcher = $contextEventDispatcher;
        $this->addressProvider = $addressProvider;
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
}
