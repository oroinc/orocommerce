<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Checks that there are enough products in the stock.
 */
class CreateOrderEventListener
{
    private InventoryQuantityManager $quantityManager;
    private ManagerRegistry $doctrine;
    private InventoryStatusHandler $statusHandler;
    private CheckoutLineItemsManager $checkoutLineItemsManager;

    public function __construct(
        InventoryQuantityManager $quantityManager,
        InventoryStatusHandler $statusHandler,
        ManagerRegistry $doctrine,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        $this->quantityManager = $quantityManager;
        $this->statusHandler = $statusHandler;
        $this->doctrine = $doctrine;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    public function onCreateOrder(ExtendableActionEvent $event): void
    {
        $context = $event->getContext();
        if (!$context instanceof WorkflowItem || !$this->isCorrectOrderData($context->getData())) {
            return;
        }

        $orderLineItems = $this->checkoutLineItemsManager->getData($context->getEntity());
        foreach ($orderLineItems as $lineItem) {
            if (!$this->quantityManager->shouldDecrement($lineItem->getProduct())) {
                continue;
            }

            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (null !== $inventoryLevel
                && $this->quantityManager->canDecrementInventory($inventoryLevel, $lineItem->getQuantity())
            ) {
                $this->quantityManager->decrementInventory($inventoryLevel, $lineItem->getQuantity());
                $this->statusHandler->changeInventoryStatusWhenDecrement($inventoryLevel);
            }
        }
    }

    public function onBeforeOrderCreate(ExtendableConditionEvent $event): void
    {
        $context = $event->getContext();
        if (!$context instanceof WorkflowItem) {
            return;
        }
        $entity = $context->getEntity();
        if (!$entity instanceof Checkout || !$this->isCorrectCheckoutEntity($entity)) {
            return;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($entity);
        foreach ($lineItems as $lineItem) {
            if (!$this->quantityManager->shouldDecrement($lineItem->getProduct())) {
                continue;
            }

            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (null === $inventoryLevel
                || !$this->quantityManager->hasEnoughQuantity($inventoryLevel, $lineItem->getQuantity())
            ) {
                $event->addError('');

                return;
            }
        }
    }

    private function getInventoryLevel(Product $product, ProductUnit $productUnit): ?InventoryLevel
    {
        return $this->doctrine->getRepository(InventoryLevel::class)
            ->getLevelByProductAndProductUnit($product, $productUnit);
    }

    private function isCorrectOrderData(mixed $data): bool
    {
        return
            $data instanceof WorkflowData
            && $data->has('order')
            && $data->get('order') instanceof Order;
    }

    private function isCorrectCheckoutEntity(Checkout $entity): bool
    {
        return
            $entity->getSource() instanceof CheckoutSource
            && $entity->getSource()->getEntity() instanceof ProductLineItemsHolderInterface;
    }
}
