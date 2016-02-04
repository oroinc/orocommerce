<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

abstract class AbstractOrderMapper implements TaxMapperInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param TaxationAddressProvider $addressProvider
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, TaxationAddressProvider $addressProvider)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->addressProvider = $addressProvider;
    }

    /**
     * @param Order $order
     * @return AbstractAddress
     */
    public function getOrderAddress(Order $order)
    {
        return $this->addressProvider->getAddressForTaxation($order);
    }

    /**
     * @param object $mappingObject
     * @return \ArrayObject
     */
    protected function getContext($mappingObject)
    {
        $event = new ContextEvent($mappingObject);
        $this->eventDispatcher->dispatch(ContextEvent::NAME, $event);

        return $event->getContext();
    }
}
