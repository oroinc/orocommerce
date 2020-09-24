<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;

/**
 * Binds value of the 'default_shopping_list_id' parameter to the datagrid.
 */
class FrontendShoppingListsGridEventListener
{
    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    /**
     * @param CurrentShoppingListManager $currentShoppingListManager
     */
    public function __construct(CurrentShoppingListManager $currentShoppingListManager)
    {
        $this->currentShoppingListManager = $currentShoppingListManager;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event): void
    {
        $current = $this->currentShoppingListManager->getCurrent();
        if (!$current instanceof ShoppingList) {
            return;
        }

        $event->getDatagrid()
            ->getParameters()
            ->set('default_shopping_list_id', $current->getId());
    }
}
