<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles logic of checkout ajax requests.
 */
class AjaxCheckoutController extends AbstractController
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
            ->getRepository(Checkout::class)->getCheckoutWithRelations($entityId);
        if (!$checkout) {
            return new JsonResponse('', Response::HTTP_NOT_FOUND);
        }

        $this->setCorrectCheckoutShippingMethodData($checkout, $request);

        return new JsonResponse($this->get(CheckoutTotalsProvider::class)->getTotalsArray($checkout));
    }

    /**
     * @param Checkout $checkout
     * @param Request  $request
     *
     * @return Checkout
     */
    protected function setCorrectCheckoutShippingMethodData(Checkout $checkout, Request $request)
    {
        $workflowTransitionData = $request->request->get('oro_workflow_transition');
        if (!is_array($workflowTransitionData)
            || !array_key_exists('shipping_method', $workflowTransitionData)
            || !array_key_exists('shipping_method_type', $workflowTransitionData)
        ) {
            return $checkout;
        }

        if (isset($workflowTransitionData['line_items_shipping_methods'])) {
            $lineItemsShippingMethods = json_decode($workflowTransitionData['line_items_shipping_methods'], true);

            $this->container->get(CheckoutLineItemsShippingManager::class)
                ->updateLineItemsShippingMethods($lineItemsShippingMethods, $checkout);
        }

        $checkout
            ->setShippingMethod($workflowTransitionData['shipping_method'])
            ->setShippingMethodType($workflowTransitionData['shipping_method_type']);

        return $checkout;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CheckoutTotalsProvider::class,
                CheckoutLineItemsShippingManager::class
            ]
        );
    }
}
