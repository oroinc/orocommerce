<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Start checkout from shopping list
 */
interface StartShoppingListCheckoutInterface
{
    /**
     * @param ShoppingList $shoppingList
     * @param bool $forceStartCheckout
     * @param bool $showErrors
     * @param bool $validateOnStartCheckout
     * @param bool $allowManualSourceRemove
     * @param bool $removeSource
     * @param bool $clearSource
     * @return array{
     *      checkout: \Oro\Bundle\CheckoutBundle\Entity\Checkout,
     *      workflowItem: \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem,
     *      redirectUrl?: string,
     *      errors?: \Doctrine\Common\Collections\Collection|array
     *  }
     */
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
