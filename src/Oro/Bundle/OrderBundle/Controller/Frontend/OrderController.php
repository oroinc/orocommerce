<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Exception;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * View orders on front store
 */
class OrderController extends AbstractController
{
    /**
     * @Route("/", name="oro_order_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="oro_order_frontend_view",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Order::class,
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_order_frontend_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_order_frontend_view")
     * @Layout()
     *
     * @param Order $order
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'data' => [
                'order' => $order,
                'totals' => (object)$this->get(TotalProcessorProvider::class)->getTotalWithSubtotalsAsArray($order),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            TotalProcessorProvider::class,
            'oro_checkout.helper.check_compare' => CheckoutCompareHelper::class
        ]);
    }

    /**
     * @Route("/checkout/{id}", name="oro_order_frontend_to_checkout", requirements={"id"="\d+"})
     * @AclAncestor("oro_order_frontend_view")
     * @param Checkout $checkout
     * @return RedirectResponse
     * @throws Exception
     */
    public function checkoutAction(Checkout $checkout): RedirectResponse
    {
        $this->get('oro_checkout.helper.check_compare')->compare($checkout);

        return $this->redirectToRoute('oro_checkout_frontend_checkout', ['id' => $checkout->getId()]);
    }
}
