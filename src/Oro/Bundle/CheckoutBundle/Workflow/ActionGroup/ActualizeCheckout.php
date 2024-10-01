<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Actualize checkout data.
 */
class ActualizeCheckout implements ActualizeCheckoutInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private UserCurrencyManager $userCurrencyManager,
        private CheckoutLineItemsFactory $checkoutLineItemsFactory,
        private CheckoutShippingMethodsProviderInterface $shippingMethodsProvider,
        private CheckoutSubtotalUpdater $checkoutSubtotalUpdater
    ) {
    }

    #[\Override]
    public function execute(
        Checkout $checkout,
        array $sourceCriteria,
        ?Website $currentWebsite,
        bool $updateData = false,
        array $checkoutData = []
    ): Checkout {
        $customerUser = $checkout->getCustomerUser();
        if ($customerUser && $updateData) {
            $checkout->setCustomer($customerUser->getCustomer());
            $checkout->setOrganization($customerUser->getOrganization());
            $checkout->setWebsite($currentWebsite);

            $this->actionExecutor->executeAction(
                'copy_values',
                [$checkout, $checkoutData]
            );
        }

        $shoppingList = $sourceCriteria['shoppingList'] ?? null;
        if ($shoppingList instanceof ShoppingList && $shoppingList->getNotes()) {
            $checkout->setCustomerNotes($shoppingList->getNotes());
        }

        $checkout->setCurrency($this->userCurrencyManager->getUserCurrency());
        $checkout->setLineItems($this->checkoutLineItemsFactory->create($checkout->getSource()?->getEntity()));

        if ($checkout->getShippingMethod()) {
            $checkout->setShippingCost($this->shippingMethodsProvider->getPrice($checkout));
        }

        $this->checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout);

        return $checkout;
    }
}
