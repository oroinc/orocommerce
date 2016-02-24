<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderTaxListener
{
    /** @var TaxManager */
    protected $taxManager;

    /**
     * @param TaxManager $taxManager
     */
    public function __construct(TaxManager $taxManager)
    {
        $this->taxManager = $taxManager;
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(Order $order, LifecycleEventArgs $event)
    {
        $this->taxManager->saveTax($order);
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $event
     */
    public function postPersist(Order $order, LifecycleEventArgs $event)
    {
        $this->taxManager->saveTax($order);
    }
}
