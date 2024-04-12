<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

interface StartQuickOrderCheckoutInterface
{
    public function execute(ShoppingList $shoppingList, ?string $transitionName = null): array;
}
