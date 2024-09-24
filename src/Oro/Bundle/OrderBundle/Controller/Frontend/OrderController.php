<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckout;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * View orders on front store
 */
class OrderController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(path: '/', name: 'oro_order_frontend_index')]
    #[Layout(vars: ['entity_class'])]
    #[Acl(
        id: 'oro_order_frontend_view',
        type: 'entity',
        class: Order::class,
        permission: 'VIEW',
        groupName: 'commerce'
    )]
    public function indexAction()
    {
        return [
            'entity_class' => Order::class,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_order_frontend_view', requirements: ['id' => '\d+'])]
    #[Layout]
    #[AclAncestor('oro_order_frontend_view')]
    public function viewAction(Order $order)
    {
        $router = $this->container->get('router')->generate('oro_order_frontend_print', ['id' => $order->getId()]);

        return [
            'data' => [
                'order' => $order,
                'grid_name' => 'order-line-items-grid-frontend',
                'print_route' => ['data' => $router],
                'totals' => (object)$this->container->get(TotalProcessorProvider::class)
                    ->getTotalWithSubtotalsAsArray($order),
            ],
        ];
    }

    /**
     * This page is identical to the 'oro_order_frontend_view' page, except that it displays all products in the
     * order grid.
     * This is necessary for printing products on the storefront.
     *
     * Since the print page uses all components that are located on the view page, we reuse it without changes.
     */
    #[Route(path: '/print/{id}', name: 'oro_order_frontend_print', requirements: ['id' => '\d+'])]
    #[Layout(action: 'oro_order_frontend_view')]
    #[AclAncestor('oro_order_frontend_view')]
    public function printAction(Order $order): array
    {
        $data = $this->viewAction($order);
        $data['data']['grid_name'] = 'order-line-items-grid-frontend-print';

        return $data;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TotalProcessorProvider::class,
            StartShoppingListCheckout::class
        ]);
    }

    /**
     * @internal layout BC route. May be used by the old theme only.
     */
    #[Route(path: '/checkout/{id}', name: 'oro_order_frontend_to_checkout', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_order_frontend_view')]
    public function checkoutAction(Checkout $checkout): RedirectResponse
    {
        $shoppingList = $checkout->getSourceEntity();
        if ($shoppingList instanceof ShoppingList) {
            $startResult = $this->container->get(StartShoppingListCheckout::class)->execute($shoppingList);
            $checkout = $startResult['checkout'];
        }

        return $this->redirectToRoute('oro_checkout_frontend_checkout', ['id' => $checkout->getId()]);
    }
}
