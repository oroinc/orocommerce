<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;

class StartShoppingListCheckout implements StartShoppingListCheckoutInterface
{
    public function __construct(
        private ShoppingListUrlProvider $shoppingListUrlProvider,
        private ShoppingListLimitManager $shoppingListLimitManager,
        private StartCheckoutInterface $startCheckout
    ) {
    }

    public function execute(
        ShoppingList $shoppingList,
        bool $forceStartCheckout = false,
        bool $showErrors = false,
        bool $validateOnStartCheckout = true,
        bool $allowManualSourceRemove = true,
        bool $removeSource = true,
        bool $clearSource = false
    ): array {
        $editLink = $this->shoppingListUrlProvider->getFrontendUrl($shoppingList);
        $sourceRemoveLabel = 'oro.frontend.shoppinglist.workflow.remove_source.label';
        $isOneShoppingList = $this->shoppingListLimitManager->isOnlyOneEnabled();

        if ($isOneShoppingList) {
            $allowManualSourceRemove = false;
            $removeSource = false;
            $clearSource = true;
        }

        return $this->startCheckout->execute(
            sourceCriteria: ['shoppingList' => $shoppingList],
            settings: [
                'allow_manual_source_remove' => $allowManualSourceRemove,
                'remove_source' => $removeSource,
                'clear_source' => $clearSource,
                'edit_order_link' => $editLink,
                'source_remove_label' => $sourceRemoveLabel
            ],
            showErrors: $showErrors,
            forceStartCheckout: $forceStartCheckout,
            validateOnStartCheckout: $validateOnStartCheckout
        );
    }
}
