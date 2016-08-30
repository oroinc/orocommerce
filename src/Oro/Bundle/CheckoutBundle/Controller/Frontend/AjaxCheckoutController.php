<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class AjaxCheckoutController extends Controller
{
    /**
     * @Route(
     *      "/get-totals-for-checkout/{entityId}",
     *      name="orob2b_checkout_frontend_totals",
     *      requirements={"entityId"="\d+"}
     * )
     * @AclAncestor("orob2b_checkout_frontend_checkout")
     *
     * @param Request $request
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function getTotalsAction(Request $request, $entityId)
    {
        /** @var Checkout $checkout */
        $checkout = $this->getDoctrine()->getManagerForClass(Checkout::class)
            ->getRepository(Checkout::class)->find($entityId);
        if (!$checkout) {
            return new JsonResponse('', Response::HTTP_NOT_FOUND);
        }

        $checkout->setShippingCost($this->getShippingCost($checkout, $request));
        return new JsonResponse($this->get('orob2b_checkout.provider.checkout_totals')->getTotalsArray($checkout));
    }

    /**
     * @param Checkout $checkout
     * @param Request $request
     * @return Price
     */
    protected function getShippingCost(Checkout $checkout, Request $request)
    {
        $workflowTransitionData = $request->request->get('oro_workflow_transition');
        if (!is_array($workflowTransitionData) || !array_key_exists('shipping_rule_config', $workflowTransitionData)) {
            return $checkout->getShippingCost();
        }
        $shippingRuleConfigId = $workflowTransitionData['shipping_rule_config'];
        $shippingRuleConfig = $this->getDoctrine()->getManagerForClass('OroShippingBundle:ShippingRuleConfiguration')
            ->getRepository('OroShippingBundle:ShippingRuleConfiguration')->find($shippingRuleConfigId);
        if (!$shippingRuleConfig) {
            return $checkout->getShippingCost();
        }

        $shippingContextProviderFactory = new ShippingContextProviderFactory();
        return $this->get('orob2b_shipping.shipping_price')->getApplicableMethodTypePrice(
            $shippingContextProviderFactory->create($checkout),
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
    }
}
