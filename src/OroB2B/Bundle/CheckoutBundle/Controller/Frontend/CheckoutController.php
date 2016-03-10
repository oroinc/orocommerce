<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class CheckoutController extends Controller
{
    /**
     * Create checkout form
     *
     * @Route(
     *     "/{id}",
     *     name="orob2b_checkout_frontend_checkout",
     *     requirements={"id"="\d+"}
     * )
     * @Layout(vars={"workflowStepName", "workflowStepOrder"})
     * @Acl(
     *      id="orob2b_checkout_frontend_checkout",
     *      type="entity",
     *      class="OroB2BCheckoutBundle:Checkout",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Checkout $checkout
     * @return array
     */
    public function checkoutAction(Checkout $checkout)
    {
        $currentStep = $checkout->getWorkflowStep();

        return [
            'workflowStepName' => $currentStep->getName(),
            'workflowStepOrder' => $currentStep->getStepOrder(),
            'data' =>
                [
                    'checkout' => $checkout,
                    'workflowStep' => $currentStep
                ]
        ];
    }

    /**
     * @TODO remove after BB-2245
     * @Route(
     *     "/success/{id}",
     *     name="orob2b_order_frontend_success",
     *     requirements={"id"="\d+"}
     * )
     * @Acl(
     *      id="orob2b_checkout_frontend_checkout",
     *      type="entity",
     *      class="OroB2BCheckoutBundle:Checkout",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Order $order
     * @return Response
     */
    public function successAction(Order $order)
    {
        return new Response($order->getId());
    }
}
