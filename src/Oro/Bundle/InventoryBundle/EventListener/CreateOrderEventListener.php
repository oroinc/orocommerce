<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Exception\InventoryLevelNotFoundException;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableConditionEvent;

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
     * @var InventoryStatusHandler
     */
    protected $statusHandler;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    public function __construct(
        InventoryQuantityManager $quantityManager,
        InventoryStatusHandler $statusHandler,
        DoctrineHelper $doctrineHelper,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        $this->quantityManager = $quantityManager;
        $this->statusHandler = $statusHandler;
        $this->doctrineHelper = $doctrineHelper;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    /**
     * @throws InventoryLevelNotFoundException
     */
    public function onCreateOrder(ExtendableActionEvent $event)
    {
        if (!$this->isCorrectOrderContext($event->getContext())) {
            return;
        }

        $orderLineItems = $this->checkoutLineItemsManager->getData($event->getContext()->getEntity());

        /** @var OrderLineItem $lineItem */
        foreach ($orderLineItems as $lineItem) {
            if (!$this->quantityManager->shouldDecrement($lineItem->getProduct())) {
                continue;
            }

            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (!$inventoryLevel) {
                throw new InventoryLevelNotFoundException();
            }
            if ($this->quantityManager->canDecrementInventory($inventoryLevel, $lineItem->getQuantity())) {
                $this->quantityManager->decrementInventory($inventoryLevel, $lineItem->getQuantity());
                $this->statusHandler->changeInventoryStatusWhenDecrement($inventoryLevel);
            }
        }
    }

    /**
     * @throws InventoryLevelNotFoundException
     */
    public function onBeforeOrderCreate(ExtendableConditionEvent $event)
    {
        if (!$this->isCorrectCheckoutContext($event->getContext())) {
            return;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($event->getContext()->getEntity());
        /** @var OrderLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();
            if (!$this->quantityManager->shouldDecrement($product)) {
                continue;
            }

            $productUnit = $lineItem->getProductUnit();
            $quantity = $lineItem->getQuantity();

            $inventoryLevel = $this->getInventoryLevel($product, $productUnit);
            if (!$inventoryLevel) {
                throw new InventoryLevelNotFoundException();
            }

            if (!$this->quantityManager->hasEnoughQuantity($inventoryLevel, $quantity)) {
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
    protected function isCorrectCheckoutContext($context)
    {
        return ($context instanceof WorkflowItem
            && $context->getEntity() instanceof Checkout
            && $context->getEntity()->getSource() instanceof CheckoutSource
            && $context->getEntity()->getSource()->getEntity() instanceof ProductLineItemsHolderInterface
        );
    }
}
