<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;

use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvent;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvents;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;

class CheckoutController extends Controller
{
    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * Create checkout form
     *
     * @Route(
     *     "/{id}/{type}",
     *     name="orob2b_checkout_frontend_checkout",
     *     requirements={"id"="\d+", "type"="\w+"}
     * )
     * @Layout(vars={"workflowStepName"})
     * @Acl(
     *      id="orob2b_checkout_frontend_checkout",
     *      type="entity",
     *      class="OroB2BCheckoutBundle:Checkout",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param int $id
     * @param null|string $type
     * @return array|Response
     * @throws \Exception
     */
    public function checkoutAction(Request $request, $id, $type = null)
    {
        $checkout = $this->getCheckout($id, $type);

        if (!$checkout) {
            throw new NotFoundHttpException(sprintf('Checkout not found'));
        }

        $workflowItem = $this->handleTransition($checkout, $request);
        $currentStep = $workflowItem->getCurrentStep();

        if ($workflowItem->getResult()->has('redirectUrl')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['redirectUrl' => $workflowItem->getResult()->get('redirectUrl')]);
            } else {
                return $this->redirect($workflowItem->getResult()->get('redirectUrl'));
            }
        }

        return [
            'workflowStepName' => $currentStep->getName(),
            'data' =>
                [
                    'checkout' => $checkout,
                    'workflowStep' => $currentStep
                ]
        ];
    }

    /**
     * @param WorkflowAwareInterface $checkout
     * @param Request $request
     * @return WorkflowItem
     * @throws \Exception
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    protected function handleTransition(WorkflowAwareInterface $checkout, Request $request)
    {
        $workflowItem = $checkout->getWorkflowItem();
        if ($request->isMethod(Request::METHOD_POST)) {
            $continueTransition = $this->get('orob2b_checkout.layout.data_provider.continue_transition')
                ->getContinueTransition($workflowItem);
            if ($continueTransition) {
                $transitionForm = $this->getTransitionForm($continueTransition, $workflowItem);

                if ($transitionForm) {
                    $transitionForm->submit($request);
                    if ($transitionForm->isValid()) {
                        $this->getWorkflowManager()->transit($workflowItem, $continueTransition->getTransition());
                    }
                } else {
                    $this->getWorkflowManager()->transit($workflowItem, $continueTransition->getTransition());
                }
            }
        } elseif ($request->query->has('transition') && $request->isMethod(Request::METHOD_GET)) {
            $transition = $request->get('transition');
            $workflow = $this->getWorkflowManager()->getWorkflow($workflowItem);
            if ($workflow->isTransitionAllowed($workflowItem, $transition)) {
                $this->getWorkflowManager()->transit($workflowItem, $transition);
            }
        }

        return $workflowItem;
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        if (!$this->workflowManager) {
            $this->workflowManager = $this->get('oro_workflow.manager');
        }

        return $this->workflowManager;
    }

    /**
     * @param TransitionData $transitionData
     * @param WorkflowItem $workflowItem
     * @return FormInterface
     */
    protected function getTransitionForm(TransitionData $transitionData, WorkflowItem $workflowItem)
    {
        return $this->get('orob2b_checkout.layout.data_provider.transition_form')
            ->getForm($transitionData, $workflowItem);
    }

    /**
     * @param int $id
     * @param string|null $type
     * @return CheckoutInterface|null
     */
    protected function getCheckout($id, $type)
    {
        if (!$type) {
            $checkout = $this->getDoctrine()->getRepository('OroB2BCheckoutBundle:Checkout')
                ->find($id);
        } else {
            $event = new CheckoutEvent();
            $event->setCheckoutId($id)
                ->setType($type);
            $this->get('event_dispatcher')->dispatch(CheckoutEvents::GET_CHECKOUT_ENTITY, $event);

            $checkout = $event->getCheckoutEntity();
        }

        return $checkout;
    }
}
