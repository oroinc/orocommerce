<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Model\AbstractStorage;

/**
 * Decrements the inventory levels of the products after a checkout is finished.
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
        $data = $event->getData();
        if (!$data || !$this->isSupportedData($data)) {
            return;
        }

        $orderLineItems = $this->checkoutLineItemsManager->getData($data->offsetGet('checkout'));
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

    private function getInventoryLevel(Product $product, ProductUnit $productUnit): ?InventoryLevel
    {
        return $this->doctrine->getRepository(InventoryLevel::class)
            ->getLevelByProductAndProductUnit($product, $productUnit);
    }

    private function isSupportedData(AbstractStorage $data): bool
    {
        return $data->offsetGet('checkout') instanceof CheckoutInterface;
    }
}
