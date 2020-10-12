<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Binds value of the 'default_shopping_list_id' parameter to the datagrid.
 * Updates shopping lists subtotals.
 */
class FrontendShoppingListsGridEventListener
{
    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    /** @var ShoppingListTotalManager */
    private $shoppingListTotalManager;

    /**
     * @param CurrentShoppingListManager $currentShoppingListManager
     * @param ShoppingListTotalManager $shoppingListTotalManager
     */
    public function __construct(
        CurrentShoppingListManager $currentShoppingListManager,
        ShoppingListTotalManager $shoppingListTotalManager
    ) {
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->shoppingListTotalManager = $shoppingListTotalManager;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event): void
    {
        $shoppingLists = $this->currentShoppingListManager->getShoppingLists();
        if ($shoppingLists) {
            $this->shoppingListTotalManager->setSubtotals($shoppingLists, true);
        }

        $current = $this->currentShoppingListManager->getCurrent();
        if (!$current instanceof ShoppingList) {
            return;
        }

        $event->getDatagrid()
            ->getParameters()
            ->set('default_shopping_list_id', $current->getId());
    }
}
