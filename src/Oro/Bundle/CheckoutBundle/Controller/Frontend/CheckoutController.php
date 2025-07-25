<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles checkout logic.
 */
class CheckoutController extends AbstractController
{
    /**
     * Creates a checkout form.
     */
    #[Route(path: '/{id}', name: 'oro_checkout_frontend_checkout', requirements: ['id' => '\d+'])]
    #[Layout(vars: ['workflowStepName', 'workflowName'])]
    #[Acl(
        id: 'oro_checkout_frontend_checkout',
        type: 'entity',
        class: Checkout::class,
        permission: 'EDIT',
        groupName: 'commerce'
    )]
    public function checkoutAction(Request $request, Checkout $checkout): array|Response
    {
        if ($checkout->isCompleted()) {
            // show "Thank You For Your Purchase!" page for a completed checkout
            // regardless of how the checkout was completed,
            // via a workflow (even if this workflow is deactivated now)
            // or via the storefront API (where any workflow is not used)
            $workflowItem = new WorkflowItem();
            $workflowItem->getData()->set('order', $checkout->getOrder());

            return [
                'workflowStepName' => 'order_created',
                'workflowName' => '',
                'data' => [
                    'checkout' => $checkout,
                    'workflowItem' => $workflowItem
                ]
            ];
        }

        $this->disableGarbageCollector();

        $event = new CheckoutRequestEvent($request, $checkout);
        $this->container->get(EventDispatcherInterface::class)->dispatch($event, 'oro_checkout.request');

        $currentStep = $event->getWorkflowStep();
        if (!$currentStep) {
            throw $this->createNotFoundException('Current Workflow Step Not Found');
        }

        $workflowItem = $this->getWorkflowItem($checkout);

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

        if ($responseData && $request->isXmlHttpRequest() && !$request->get('layout_block_ids')) {
            return new JsonResponse($responseData);
        }

        return [
            'workflowStepName' => $currentStep->getName(),
            'workflowName' => $workflowItem->getWorkflowName(),
            'data' => [
                'checkout' => $checkout,
                'workflowItem' => $workflowItem,
                'workflowStep' => $currentStep
            ]
        ];
    }

    /**
     * Disables garbage collector for "prod" mode requests to improve execution speed
     * of the action which perform a lot of stuff.
     */
    private function disableGarbageCollector(): void
    {
        if ($this->container->get(KernelInterface::class)->getEnvironment() === 'prod') {
            gc_disable();
        }
    }

    private function getWorkflowItem(CheckoutInterface $checkout): WorkflowItem
    {
        $item =  $this->container->get(CheckoutWorkflowHelper::class)->getWorkflowItem($checkout);
        if (!$item) {
            throw $this->createNotFoundException('Unable to find correct WorkflowItem for current checkout');
        }

        return $item;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                KernelInterface::class,
                CheckoutWorkflowHelper::class,
                EventDispatcherInterface::class,
            ]
        );
    }
}
