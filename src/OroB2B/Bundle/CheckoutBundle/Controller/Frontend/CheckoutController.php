<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\HttpFoundation\Response;

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
        $orderAddress = new OrderAddress();
        $billingForm = $this->createForm(
            OrderAddressType::NAME,
            $orderAddress,
            [
                'object' => $checkout,
                'addressType' => AddressType::TYPE_BILLING,
                'isEditEnabled' => true
            ]
        );

        $formView = $billingForm->createView();
        $formView->vars['class_prefix'] = '';
        return [
            'workflowStepName' => $currentStep->getName(),
            'workflowStepOrder' => $currentStep->getStepOrder(),
            'data' =>
                [
                    'checkout' => $checkout,
                    'billingForm' => $formView,
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
