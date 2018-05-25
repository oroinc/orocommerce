<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles checkout logic
 */
class CheckoutController extends Controller
{
    /**
     * Create checkout form
     *
     * @Route(
     *     "/{id}",
     *     name="oro_checkout_frontend_checkout",
     *     requirements={"id"="\d+"}
     * )
     * @Layout(vars={"workflowStepName", "workflowName"})
     * @Acl(
     *      id="oro_checkout_frontend_checkout",
     *      type="entity",
     *      class="OroCheckoutBundle:Checkout",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param Checkout $checkout
     * @return array|Response
     * @throws \Exception
     */
    public function checkoutAction(Request $request, Checkout $checkout)
    {
        $workflowItem = $this->getWorkflowItem($checkout);

        $currentStep = $this->getCheckoutWorkflowHelper()
            ->processWorkflowAndGetCurrentStep($request, $workflowItem, $checkout);

        $responseData = [];
        if ($workflowItem->getResult()->has('responseData')) {
            $responseData['responseData'] = $workflowItem->getResult()->get('responseData');
        }
        if ($workflowItem->getResult()->has('redirectUrl')) {
            if ($request->isXmlHttpRequest()) {
                $responseData['redirectUrl'] = $workflowItem->getResult()->get('redirectUrl');
            } else {
                return $this->redirect($workflowItem->getResult()->get('redirectUrl'));
            }
        }

        if ($responseData && $request->isXmlHttpRequest()) {
            return new JsonResponse($responseData);
        }

        return [
            'workflowStepName' => $currentStep->getName(),
            'workflowName' => $workflowItem->getWorkflowName(),
            'data' =>
                [
                    'checkout' => $checkout,
                    'workflowItem' => $workflowItem,
                    'workflowStep' => $currentStep
                ]
        ];
    }

    /**
     * @param CheckoutInterface $checkout
     *
     * @return mixed|WorkflowItem
     */
    protected function getWorkflowItem(CheckoutInterface $checkout)
    {
        return $this->getCheckoutWorkflowHelper()->getWorkflowItem($checkout);
    }

    /**
     * @return CheckoutWorkflowHelper
     */
    private function getCheckoutWorkflowHelper()
    {
        return $this->get('oro_checkout.helper.checkout_workflow_helper');
    }
}
