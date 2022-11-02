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
    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    /** @var ShoppingListTotalManager */
    private $shoppingListTotalManager;

    /** @var UserCurrencyManager */
    private $userCurrencyManager;

    public function __construct(
        CurrentShoppingListManager $currentShoppingListManager,
        ShoppingListTotalManager $shoppingListTotalManager,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->shoppingListTotalManager = $shoppingListTotalManager;
        $this->userCurrencyManager = $userCurrencyManager;
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
