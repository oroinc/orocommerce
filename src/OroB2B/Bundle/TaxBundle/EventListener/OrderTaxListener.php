<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

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
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(LifecycleEventArgs $event)
    {
        /** @var Order $order */
        $order = $event->getEntity();
        if (!$this->isApplicable($order)) {
            return;
        }

        $this->taxManager->saveTax($order);
    }

    /**
     * @param  object  $entity
     * @return boolean
     */
    protected function isApplicable($entity)
    {
        return $entity instanceof Order;
    }
}
