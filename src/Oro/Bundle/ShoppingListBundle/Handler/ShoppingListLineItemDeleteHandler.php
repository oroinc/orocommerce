<?php

namespace Oro\Bundle\ShoppingListBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * The delete handler for shopping list LineItem entity.
 */
class ShoppingListLineItemDeleteHandler extends AbstractEntityDeleteHandler
{
    /** @var ShoppingListTotalManager */
    private $totalManager;

    public function __construct(ShoppingListTotalManager $totalManager)
    {
        $this->totalManager = $totalManager;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $options): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $options[self::ENTITY];
        $shoppingList = $lineItem->getShoppingList();
        if (null !== $shoppingList) {
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
            /** @var LineItem $lineItem */
            $lineItem = $options[self::ENTITY];
            $shoppingList = $lineItem->getShoppingList();
            if (null !== $shoppingList) {
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
        /** @var LineItem $entity */

        $shoppingList = $entity->getShoppingList();
        if (null !== $shoppingList) {
            $shoppingList->removeLineItem($entity);
        }
        parent::deleteWithoutFlush($entity, $options);
    }
}
