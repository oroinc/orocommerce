<?php

namespace Oro\Bundle\ShoppingListBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Delete handler for {@see ProductKitItemLineItem} entity.
 */
class ProductKitItemLineItemDeleteHandler extends AbstractEntityDeleteHandler
{
    private ShoppingListTotalManager $totalManager;

    public function __construct(ShoppingListTotalManager $totalManager)
    {
        $this->totalManager = $totalManager;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $options): void
    {
        /** @var ProductKitItemLineItem $productKitItemLineItem */
        $productKitItemLineItem = $options[self::ENTITY];
        $shoppingList = $productKitItemLineItem->getLineItem()?->getShoppingList();
        if ($shoppingList !== null) {
            $this->totalManager->recalculateTotals($shoppingList, false);
        }

        parent::flush($options);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(array $listOfOptions): void
    {
        $processedShoppingLists = [];
        foreach ($listOfOptions as $options) {
            /** @var ProductKitItemLineItem $productKitItemLineItem */
            $productKitItemLineItem = $options[self::ENTITY];
            $shoppingList = $productKitItemLineItem->getLineItem()?->getShoppingList();
            if ($shoppingList !== null) {
                $shoppingListHash = spl_object_hash($shoppingList);
                if (!isset($processedShoppingLists[$shoppingListHash])) {
                    $this->totalManager->recalculateTotals($shoppingList, false);
                    $processedShoppingLists[$shoppingListHash] = true;
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
        /** @var ProductKitItemLineItem $entity */

        $lineItem = $entity->getLineItem();
        $lineItem?->removeKitItemLineItem($entity);

        parent::deleteWithoutFlush($entity, $options);
    }
}
