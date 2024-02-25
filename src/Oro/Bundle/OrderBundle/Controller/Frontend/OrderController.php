<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Exception;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
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
     *
     * @param Order $order
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_order_frontend_view', requirements: ['id' => '\d+'])]
    #[Layout]
    #[AclAncestor('oro_order_frontend_view')]
    public function viewAction(Order $order)
    {
        return [
            'data' => [
                'order' => $order,
                'totals' => (object)$this->container->get(TotalProcessorProvider::class)
                    ->getTotalWithSubtotalsAsArray($order),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TotalProcessorProvider::class,
            'oro_checkout.helper.check_compare' => CheckoutCompareHelper::class
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/checkout/{id}', name: 'oro_order_frontend_to_checkout', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_order_frontend_view')]
    public function checkoutAction(Checkout $checkout): RedirectResponse
    {
        $this->container->get('oro_checkout.helper.check_compare')->compare($checkout);

        return $this->redirectToRoute('oro_checkout_frontend_checkout', ['id' => $checkout->getId()]);
    }
}
