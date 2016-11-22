<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxCheckoutController extends Controller
{
    /**
     * @Route(
     *      "/get-totals-for-checkout/{entityId}",
     *      name="oro_checkout_frontend_totals",
     *      requirements={"entityId"="\d+"}
     * )
     * @AclAncestor("oro_checkout_frontend_checkout")
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
        return new JsonResponse($this->get('oro_checkout.provider.checkout_totals')->getTotalsArray($checkout));
    }

    /**
     * @param Checkout $checkout
     * @param Request $request
     * @return Price
     */
    protected function getShippingCost(Checkout $checkout, Request $request)
    {
        $workflowTransitionData = $request->request->get('oro_workflow_transition');
        if (!is_array($workflowTransitionData)
            || !array_key_exists('shipping_method', $workflowTransitionData)
            || !array_key_exists('shipping_method_type', $workflowTransitionData)
        ) {
            return $checkout->getShippingCost();
        }

        $shippingContextProviderFactory = $this->get('oro_checkout.factory.shipping_context_provider_factory');

        return $this->get('oro_shipping.shipping_price.provider')->getPrice(
            $shippingContextProviderFactory->create($checkout),
            $workflowTransitionData['shipping_method'],
            $workflowTransitionData['shipping_method_type']
        );
    }
}
