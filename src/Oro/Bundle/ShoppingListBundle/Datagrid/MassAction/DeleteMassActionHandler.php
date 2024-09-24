<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\MassAction;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler as ParentHandler;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Recalculates totals after line items are deleted.
 */
class DeleteMassActionHandler extends ParentHandler
{
    private ShoppingListTotalManager $shoppingListTotalManager;
    /** @var ShoppingList[] */
    private array $shoppingLists = [];

    public function setShoppingListTotalManager(ShoppingListTotalManager $shoppingListTotalManager): void
    {
        $this->shoppingListTotalManager = $shoppingListTotalManager;
    }

    #[\Override]
    protected function processDelete(object $entity, EntityManagerInterface $manager): void
    {
        $shoppingList = $entity->getShoppingList();
        if ($shoppingList && $shoppingList->getId()) {
            $this->shoppingLists[$shoppingList->getId()] = $shoppingList;
        }

        parent::processDelete($entity, $manager);
    }

    #[\Override]
    protected function finishBatch(EntityManagerInterface $manager): void
    {
        $manager->flush();

        foreach ($this->shoppingLists as $shoppingList) {
            $this->shoppingListTotalManager->recalculateTotals($shoppingList, false);
        }

        $this->shoppingLists = [];

        parent::finishBatch($manager);
    }
}
