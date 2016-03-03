<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutController extends Controller
{
    /**
     * Create checkout form
     *
     * @Route(
     *     "/{id}",
     *     name="orob2b_checkout_frontend_checkout",
     *     defaults={"id" = null},
     *     requirements={"id"="\d+"}
     * )
     * @ParamConverter(
     *     "checkout",
     *     class="OroB2BCheckoutBundle:Checkout",
     *     isOptional="true",
     *     options={"id" = "id"}
     *     )
     * @Layout(vars={"page"})
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
        if (!$checkout) {
            $checkout = new Checkout();
        }
        $page = $request->query->get('page', 1);

        if ($page == 5) {
            $formBuilder = $this->createFormBuilder();
            $formBuilder->add('lastShip', 'text', array(
                'label' => '',
                'required' => false
            ));
            $formBuilder->add('poNumber', 'text', array(
                'label' => ' ',
                'required' => false
            ));
            $formBuilder->add('note', 'textarea', array(
                'label' => ' ',
                'required' => false
            ));

            $reviewForm = new FormAccessor($formBuilder->getForm());

            return [
                'page' => $page,
                'data' => [
                    'checkout' => $checkout,
                    'page' => $page,
                    'form' => $reviewForm,
                ]
            ];
        } else {
            return [
                'page' => $page,
                'data' => ['checkout' => $checkout, 'page' => $page]
            ];
        }
    }
}
