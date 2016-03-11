<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

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
     * @Layout(vars={"workflowStepName", "workflowStepOrder","checkout"})
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
            'checkout' => $checkout,
            'data' =>
                [
                    'checkout' => $checkout,
                    'workflowStep' => $currentStep
                ]
        ];
    }
}
