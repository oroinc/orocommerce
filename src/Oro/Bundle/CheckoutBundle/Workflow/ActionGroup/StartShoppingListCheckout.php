<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;

/**
 * Start checkout from shopping list
 */
class StartShoppingListCheckout implements StartShoppingListCheckoutInterface
{
    private string $sourceRemoveLabel = 'oro.frontend.shoppinglist.workflow.remove_source.label';

    public function __construct(
        private ShoppingListUrlProvider $shoppingListUrlProvider,
        private ShoppingListLimitManager $shoppingListLimitManager,
        private StartCheckoutInterface $startCheckout
    ) {
    }

    public function setSourceRemoveLabel(string $sourceRemoveLabel): void
    {
        $this->sourceRemoveLabel = $sourceRemoveLabel;
    }

    #[\Override]
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
                'source_remove_label' => $this->sourceRemoveLabel
            ],
            showErrors: $showErrors,
            forceStartCheckout: $forceStartCheckout,
            validateOnStartCheckout: $validateOnStartCheckout
        );
    }
}
