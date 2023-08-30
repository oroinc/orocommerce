<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

/**
 * Calculates the subtotal and saves the cached value for any customer user only when customer user opens the page for
 * viewing or editing the shopping list.
 */
class FrontendShoppingListTotalListener
{
    private const SUPPORTED_ROUTES = ['oro_shopping_list_frontend_view', 'oro_shopping_list_frontend_update'];

    public function __construct(
        private CustomerUserProvider $customerUserProvider,
        private ShoppingListTotalManager $shoppingListTotalManager
    ) {
    }

    public function onKernelController(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        $customerUser = $this->customerUserProvider->getLoggedUser();
        if ($customerUser && in_array($request->attributes->get('_route'), self::SUPPORTED_ROUTES)) {
            $arguments = $event->getArguments();
            $shoppingList = reset($arguments);
            if (!$shoppingList instanceof ShoppingList) {
                return;
            }

            if ($shoppingList->getCustomerUser() === $customerUser) {
                return;
            }

            $this->shoppingListTotalManager->setSubtotalsForCustomerUser($shoppingList, $customerUser);
        }
    }
}
