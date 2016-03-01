<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;

class OrderTaxListener
{
    /** @var TaxManager */
    protected $taxManager;

    /** @var TaxValue[] */
    protected $taxValues = [];

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
    public function prePersist(Order $order, LifecycleEventArgs $event)
    {
        /**
         * Entities without ID can't be processed in preFlush, because flush() call required.
         * Create new TaxValue entities with empty "entityId" property.
         * Fill this property in postPersist event
         */
        if (!$order->getId()) {
            try {
                $event->getEntityManager()->persist($this->getTaxValue($order));
            } catch (TaxationDisabledException $e) {
                // Taxation disabled, skip tax saving
            }

        }
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $event
     */
    public function postPersist(Order $order, LifecycleEventArgs $event)
    {
        $key = $this->getKey($order);
        if (array_key_exists($key, $this->taxValues)) {
            $orderId = $order->getId();
            $taxValue = $this->taxValues[$key];
            $taxValue->setEntityId($orderId);

            $uow = $event->getEntityManager()->getUnitOfWork();
            $uow->propertyChanged($taxValue, 'entityId', null, $orderId);
            $uow->scheduleExtraUpdate($taxValue, [
                'entityId' => [null, $orderId]
            ]);

            unset($this->taxValues[$key]);
        }
    }

    /**
     * @param Order $order
     * @param PreFlushEventArgs $event
     */
    public function preFlush(Order $order, PreFlushEventArgs $event)
    {
        // Entities with ID can be processed in preFlush
        if ($order->getId()) {
            try {
                $this->taxManager->saveTax($order, false);
                foreach ($order->getLineItems() as $item) {
                    if ($item->getId()) {
                        $this->taxManager->saveTax($item);
                    } else {
                        $event->getEntityManager()->persist($this->getTaxValue($item));
                    }
                }
            } catch (TaxationDisabledException $e) {
                // Taxation disabled, skip tax saving
            }
        }
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $event
     */
    public function preRemove(Order $order, LifecycleEventArgs $event)
    {
        $this->taxManager->removeTax($order);
    }

    /**
     * @param object $object
     * @return false|TaxValue
     */
    protected function getTaxValue($object)
    {
        $taxValue = $this->taxManager->createTaxValue($object);
        $this->taxValues[$this->getKey($object)] = $taxValue;

        return $taxValue;
    }

    /**
     * @param $object
     * @return string
     */
    protected function getKey($object)
    {
        return spl_object_hash($object);
    }
}
