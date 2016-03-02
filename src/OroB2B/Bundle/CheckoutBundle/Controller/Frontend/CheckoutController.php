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
        $page = $request->query->get('page');
        $page = $page ?: 1;
        $checkoutSummaryData = $this->get('orob2b_checkout.data_provider.manager')->getData($checkout);

        return [
            'page' => $page,
            'data' =>
                ['checkout' => $checkout, 'page' => $page, 'summary' => $checkoutSummaryData]
        ];
    }
}
