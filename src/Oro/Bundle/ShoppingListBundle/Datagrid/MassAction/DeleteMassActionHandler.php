<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\MassAction;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler as ParentHandler;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Recalculates totals after line items are deleted.
 */
class DeleteMassActionHandler extends ParentHandler
{
    /**
     * @var ShoppingListTotalManager
     */
    private $shoppingListTotalManager;

    /** @var ShoppingList[] */
    private $shoppingLists = [];

    /**
     * @param ShoppingListTotalManager $shoppingListTotalManager
     */
    public function setShoppingListTotalManager($shoppingListTotalManager): void
    {
        $this->shoppingListTotalManager = $shoppingListTotalManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function processDelete($entity, EntityManager $manager): self
    {
        $shoppingList = $entity->getShoppingList();
        if ($shoppingList && $shoppingList->getId()) {
            $this->shoppingLists[$shoppingList->getId()] = $shoppingList;
        }

        return parent::processDelete($entity, $manager);
    }

    /**
     * {@inheritdoc}
     */
    protected function finishBatch(EntityManager $manager): void
    {
        $manager->flush();

        foreach ($this->shoppingLists as $shoppingList) {
            $this->shoppingListTotalManager->recalculateTotals($shoppingList, false);
        }

        $this->shoppingLists = [];

        parent::finishBatch($manager);
    }
}
