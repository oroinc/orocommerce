<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManagerInterface;
use Oro\Bundle\InventoryBundle\Exception\InventoryLevelNotFoundException;
use Oro\Bundle\InventoryBundle\Validator\InventoryLevelOrderValidator;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableConditionEvent;

class CreateOrderEventListener
{
    /**
     * @var InventoryQuantityManagerInterface
     */
    protected $quantityManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var InventoryStatusHandler
     */
    protected $statusHandler;

    /**
     * @var InventoryLevelOrderValidator
     */
    protected $orderValidator;

    /**
     * @param InventoryQuantityManagerInterface $quantityManager
     * @param InventoryStatusHandler $statusHandler
     * @param InventoryLevelOrderValidator $orderValidator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        InventoryQuantityManagerInterface $quantityManager,
        InventoryStatusHandler $statusHandler,
        InventoryLevelOrderValidator $orderValidator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->quantityManager = $quantityManager;
        $this->orderValidator = $orderValidator;
        $this->statusHandler = $statusHandler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ExtendableActionEvent $event
     * @throws InventoryLevelNotFoundException
     */
    public function onCreateOrder(ExtendableActionEvent $event)
    {
        if (!$this->isCorrectOrderContext($event->getContext())) {
            return;
        }

        $orderLineItems = $event->getContext()->getData()->get('order')->getLineItems();

        /** @var OrderLineItem $lineItem */
        foreach ($orderLineItems as $lineItem) {
            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (!$inventoryLevel) {
                throw new InventoryLevelNotFoundException();
            }
            $this->quantityManager->decrementInventory($inventoryLevel, $lineItem->getQuantity());
            $this->statusHandler->changeInventoryStatusWhenDecrement($inventoryLevel);
        }
    }

    /**
     * @param ExtendableConditionEvent $event
     * @throws InventoryLevelNotFoundException
     */
    public function onBeforeOrderCreate(ExtendableConditionEvent $event)
    {
        if (!$this->isCorrectShoppingListContext($event->getContext())) {
            return;
        }

        $lineItems = $event->getContext()->getEntity()->getSource()->getShoppingList()->getLineItems();
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (!$inventoryLevel) {
                throw new InventoryLevelNotFoundException();
            }

            if (!$this->orderValidator->hasEnoughQuantity($inventoryLevel, $lineItem->getQuantity())) {
                $event->addError('');

                return;
            }
        }
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return InventoryLevel
     */
    protected function getInventoryLevel(Product $product, ProductUnit $productUnit)
    {
        return $this->doctrineHelper->getEntityRepository(InventoryLevel::class)->getLevelByProductAndProductUnit(
            $product,
            $productUnit
        );
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isCorrectOrderContext($context)
    {
        return ($context instanceof WorkflowItem
            && $context->getData() instanceof WorkflowData
            && $context->getData()->has('order')
            && $context->getData()->get('order') instanceof Order
        );
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isCorrectShoppingListContext($context)
    {
        return ($context instanceof WorkflowItem
            && $context->getEntity() instanceof Checkout
            && $context->getEntity()->getSource() instanceof CheckoutSource
            && $context->getEntity()->getSource()->getShoppingList() instanceof ShoppingList
        );
    }
}
