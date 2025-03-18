<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Binds value of the 'default_shopping_list_id' parameter to the datagrid.
 * Updates shopping lists subtotals.
 */
class FrontendShoppingListsGridEventListener
{
    public function __construct(
        private CurrentShoppingListManager $currentShoppingListManager,
        private ShoppingListTotalManager $shoppingListTotalManager,
        private UserCurrencyManager $userCurrencyManager
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $params = $event->getDatagrid()->getParameters();
        $params->set('current_currency', $this->userCurrencyManager->getUserCurrency());

        $shoppingLists = $this->currentShoppingListManager->getShoppingLists();
        if ($shoppingLists) {
            $this->shoppingListTotalManager->setSubtotals($shoppingLists, true);
        }

        $current = $this->currentShoppingListManager->getCurrent();
        if (!$current instanceof ShoppingList) {
            return;
        }

        $params->set('default_shopping_list_id', $current->getId());
    }
}
