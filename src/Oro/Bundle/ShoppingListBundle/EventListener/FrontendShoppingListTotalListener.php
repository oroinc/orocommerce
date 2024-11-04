<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Calculates the subtotal and saves the cached value for any customer user only when customer user opens the page for
 * viewing or editing the shopping list.
 */
class FrontendShoppingListTotalListener implements ServiceSubscriberInterface
{
    private CustomerUserProvider $customerUserProvider;
    private ContainerInterface $container;

    public function __construct(CustomerUserProvider $customerUserProvider, ContainerInterface $container)
    {
        $this->customerUserProvider = $customerUserProvider;
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [ShoppingListTotalManager::class];
    }

    public function onKernelController(ControllerArgumentsEvent $event): void
    {
        $customerUser = $this->customerUserProvider->getLoggedUser();
        if (null === $customerUser) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        if ('oro_shopping_list_frontend_view' !== $route && 'oro_shopping_list_frontend_update' !== $route) {
            return;
        }

        $arguments = $event->getArguments();
        $shoppingList = reset($arguments);
        if (!$shoppingList instanceof ShoppingList) {
            return;
        }

        if ($shoppingList->getCustomerUser() === $customerUser) {
            return;
        }

        $this->getShoppingListTotalManager()->setSubtotalsForCustomerUser($shoppingList, $customerUser);
    }

    private function getShoppingListTotalManager(): ShoppingListTotalManager
    {
        return $this->container->get(ShoppingListTotalManager::class);
    }
}
