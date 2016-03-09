<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class TransitionFormDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param WorkflowManager $workflowManager
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        WorkflowManager $workflowManager,
        FormFactoryInterface $formFactory
    ) {
        $this->workflowManager = $workflowManager;
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        $workflowItem = $checkout->getWorkflowItem();
        $continueTransition = $this->getContinueTransition($workflowItem);

        if ($continueTransition) {
            return $this->getForm($continueTransition, $workflowItem)->createView();
        }

        return null;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return null|Transition
     */
    protected function getContinueTransition(WorkflowItem $workflowItem)
    {
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        foreach ($transitions as $transition) {
            $frontendOptions = $transition->getFrontendOptions();
            if ($transition->hasForm() && !empty($frontendOptions['is_checkout_continue'])) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     * @return FormInterface
     */
    protected function getForm(Transition $transition, WorkflowItem $workflowItem)
    {
        return $this->formFactory->create(
            $transition->getFormType(),
            $workflowItem->getData(),
            array_merge(
                $transition->getFormOptions(),
                array(
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                )
            )
        );
    }
}
