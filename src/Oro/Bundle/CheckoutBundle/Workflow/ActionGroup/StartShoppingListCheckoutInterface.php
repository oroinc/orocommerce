<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

interface StartShoppingListCheckoutInterface
{
    public function execute(
        ShoppingList $shoppingList,
        bool $forceStartCheckout = false,
        bool $showErrors = false,
        bool $validateOnStartCheckout = true,
        bool $allowManualSourceRemove = true,
        bool $removeSource = true,
        bool $clearSource = false
    ): array;
}
