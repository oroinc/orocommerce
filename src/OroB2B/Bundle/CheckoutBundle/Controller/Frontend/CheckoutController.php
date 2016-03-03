<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutController extends Controller
{
    /**
     * Create checkout form
     *
     * @Route(
     *     "/{checkoutId}",
     *     name="orob2b_checkout_frontend_checkout",
     *     defaults={"checkoutId" = null},
     *     requirements={"checkoutId"="\d+"}
     * )
     * @ParamConverter(
     *     "checkoutId",
     *     class="OroB2BCheckoutBundle:Checkout",
     *     isOptional="true",
     *     options={"id" = "checkoutId"}
     *     )
     * @Layout(vars={"page", "page"})
     * @Acl(
     *      id="orob2b_checkout_frontend_checkout",
     *      type="entity",
     *      class="OroB2BCheckoutBundle:Checkout",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Checkout|null $checkout
     * @param Request $request
     * @return array
     */
    public function checkoutAction(Request $request, Checkout $checkout = null)
    {
        $user = $this->getUser();
        $order = new Checkout();
        $orderAddress = new OrderAddress();
        $order->setAccountUser($user);
        $billingForm = $this->createForm(
            OrderAddressType::NAME,
            $orderAddress,
            [
                'object' => $order,
                'addressType' => AddressType::TYPE_BILLING,
                'application' => OrderAddressType::APPLICATION_FRONTEND
            ]
        );

        $formView = $billingForm->createView();
        $formView->vars['class_prefix'] = '';
        return [
            'page' => $page,
            'data' =>
                [
                    'checkout' => $checkout,
                    'page' => $page,
                    'user' => $user,
                    'billingForm' => $formView,
                ]
        ];
    }
}
