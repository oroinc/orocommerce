<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles checkout logic
 */
class CheckoutController extends AbstractController
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
        $this->disableGarbageCollector();

        $this->get(PreloadingManager::class)->preloadInEntities(
            $checkout->getLineItems()->toArray(),
            [
                'product' => [
                    'backOrder' => [],
                    'category' => [
                        'backOrder' => [],
                        'decrementQuantity' => [],
                        'highlightLowInventory' => [],
                        'inventoryThreshold' => [],
                        'isUpcoming' => [],
                        'lowInventoryThreshold' => [],
                        'manageInventory' => [],
                        'maximumQuantityToOrder' => [],
                        'minimumQuantityToOrder' => [],
                    ],
                    'decrementQuantity' => [],
                    'highlightLowInventory' => [],
                    'inventoryThreshold' => [],
                    'isUpcoming' => [],
                    'lowInventoryThreshold' => [],
                    'manageInventory' => [],
                    'maximumQuantityToOrder' => [],
                    'minimumQuantityToOrder' => [],
                ],
            ]
        );

        $currentStep = $this->get(CheckoutWorkflowHelper::class)
            ->processWorkflowAndGetCurrentStep($request, $checkout);

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
            'data' =>
                [
                    'checkout' => $checkout,
                    'workflowItem' => $workflowItem,
                    'workflowStep' => $currentStep
                ]
        ];
    }

    /**
     *  Disables Garbage collector to improve execution speed of the action which perform a lot of stuff
     *  Only for Prod mode requests
     */
    private function disableGarbageCollector()
    {
        if ($this->get(KernelInterface::class)->getEnvironment() === 'prod') {
            gc_disable();
        }
    }

    /**
     * @param CheckoutInterface $checkout
     *
     * @return mixed|WorkflowItem
     * @throws WorkflowException
     */
    protected function getWorkflowItem(CheckoutInterface $checkout)
    {
        $item =  $this->get(CheckoutWorkflowHelper::class)->getWorkflowItem($checkout);

        if (!$item) {
            throw $this->createNotFoundException('Unable to find correct WorkflowItem for current checkout');
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                KernelInterface::class,
                CheckoutWorkflowHelper::class,
                PreloadingManager::class,
            ]
        );
    }
}
