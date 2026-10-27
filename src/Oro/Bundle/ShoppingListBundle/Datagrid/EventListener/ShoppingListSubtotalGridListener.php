<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Recalculates invalidated shopping list totals for the rows of the back-office shopping list grids.
 */
class ShoppingListSubtotalGridListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ShoppingListTotalManager $shoppingListTotalManager
    ) {
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $recordsWithoutSubtotal = [];
        foreach ($event->getRecords() as $record) {
            if ($record->getValue('subtotal') === null) {
                $recordsWithoutSubtotal[$record->getValue('id')] = $record;
            }
        }
        if (!$recordsWithoutSubtotal) {
            return;
        }

        $shoppingLists = $this->doctrine->getRepository(ShoppingList::class)
            ->findBy(['id' => array_keys($recordsWithoutSubtotal)]);
        foreach ($shoppingLists as $shoppingList) {
            $shoppingListTotal = $this->shoppingListTotalManager->getShoppingListTotalForCurrency(
                $shoppingList,
                $shoppingList->getCurrency()
            );

            $record = $recordsWithoutSubtotal[$shoppingList->getId()];
            $record->setValue('subtotal', $shoppingListTotal->getSubtotal()->getAmount());
            $record->setValue('currency', $shoppingListTotal->getCurrency());
        }

        $this->doctrine->getManagerForClass(ShoppingListTotal::class)->flush();
    }
}
