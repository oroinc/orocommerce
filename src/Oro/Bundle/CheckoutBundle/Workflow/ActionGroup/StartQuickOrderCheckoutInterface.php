<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Start checkout from quick order form.
 */
interface StartQuickOrderCheckoutInterface
{
    /**
     * @param ShoppingList $shoppingList Source shopping list
     * @param string|null $transitionName The name of start transition to be used to start checkout workflow
     *
     * @return array{
     *      checkout: \Oro\Bundle\CheckoutBundle\Entity\Checkout,
     *      workflowItem: \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem,
     *      redirectUrl?: string,
     *      errors?: \Doctrine\Common\Collections\Collection|array
     *  }
     */
    public function execute(ShoppingList $shoppingList, ?string $transitionName = null): array;
}
