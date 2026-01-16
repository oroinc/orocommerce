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
    private const string ASSOCIATED_LIST = 'associatedList';

    /** @var ShoppingListTotalManager */
    private $totalManager;

    public function __construct(ShoppingListTotalManager $totalManager)
    {
        $this->totalManager = $totalManager;
    }

    #[\Override]
    public function delete($entity, bool $flush = true, array $options = []): ?array
    {
        $flushOptions = $options;
        $flushOptions[self::ENTITY] = $entity;
        $flushOptions[self::ASSOCIATED_LIST] = $entity->getAssociatedList();

        $this->assertDeleteGranted($entity);
        $this->deleteWithoutFlush($entity, $options);

        if ($flush) {
            $this->flush($flushOptions);

            return null;
        }

        return $flushOptions;
    }

    #[\Override]
    public function flush(array $options): void
    {
        $shoppingList = $options[self::ASSOCIATED_LIST] ?? null;
        if (null !== $shoppingList) {
            $this->totalManager->invalidateAndRecalculateTotals($shoppingList, false);
        }
        parent::flush($options);
    }

    #[\Override]
    public function flushAll(array $listOfOptions): void
    {
        $processedShoppingLists = [];
        foreach ($listOfOptions as $options) {
            $shoppingList = $options[self::ASSOCIATED_LIST] ?? null;
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

    #[\Override]
    protected function deleteWithoutFlush($entity, array $options): void
    {
        /** @var LineItem $entity */
        $entity->removeFromAssociatedList();

        parent::deleteWithoutFlush($entity, $options);
    }
}
