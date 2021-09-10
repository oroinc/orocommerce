<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

/**
 * The delete handler for OrderLineItem entity.
 */
class OrderLineItemDeleteHandler extends AbstractEntityDeleteHandler
{
    /** @var TotalHelper */
    private $totalHelper;

    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $options): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $options[self::ENTITY];
        $order = $lineItem->getOrder();
        if (null !== $order) {
            $this->totalHelper->fill($order);
        }
        parent::flush($options);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(array $listOfOptions): void
    {
        $processedOrders = [];
        foreach ($listOfOptions as $options) {
            /** @var OrderLineItem $lineItem */
            $lineItem = $options[self::ENTITY];
            $order = $lineItem->getOrder();
            if (null !== $order) {
                $orderHash = spl_object_hash($order);
                if (!isset($processedOrders[$orderHash])) {
                    $this->totalHelper->fill($order);
                    $processedOrders[$orderHash] = true;
                }
            }
        }
        parent::flushAll($listOfOptions);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteWithoutFlush($entity, array $options): void
    {
        /** @var OrderLineItem $entity */

        $order = $entity->getOrder();
        if (null !== $order) {
            $order->removeLineItem($entity);
        }
        parent::deleteWithoutFlush($entity, $options);
    }
}
