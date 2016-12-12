<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;

class CreateOrderEventListener
{
    /**
     * @var InventoryQuantityManager
     */
    protected $quantityManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param InventoryQuantityManager $quantityManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(InventoryQuantityManager $quantityManager, DoctrineHelper $doctrineHelper)
    {
        $this->quantityManager = $quantityManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ExtendableActionEvent $event
     */
    public function onCreateOrder(ExtendableActionEvent $event)
    {
        if (!$this->isCorrectContext($event)) {
            return;
        }

        $orderLineItems = $event->getContext()->getData()->get('order')->getLineItems();
        $inventoryRepo = $this->doctrineHelper->getEntityRepository(InventoryLevel::class);

        /** @var OrderLineItem $lineItem */
        foreach ($orderLineItems as $lineItem) {
            $inventoryLevel = $inventoryRepo->getLevelByProductAndProductUnit(
                $lineItem->getProduct(),
                $lineItem->getProductUnit()
            );
            $this->quantityManager->decrementInventory($inventoryLevel, $lineItem->getQuantity());
        }
    }

    /**
     * @param ExtendableActionEvent $event
     * @return bool
     */
    protected function isCorrectContext(ExtendableActionEvent $event)
    {
        $context = $event->getContext();

        return ($context instanceof WorkflowItem
            && $context->getData() instanceof WorkflowData
            && $context->getData()->has('order')
            && $context->getData()->get('order') instanceof Order
        );
    }
}
