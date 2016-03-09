<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class TransitionFormDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var DataProviderInterface
     */
    protected $continueTransitionDataProvider;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function setContinueTransitionDataProvider(DataProviderInterface $continueTransitionDataProvider)
    {
        $this->continueTransitionDataProvider = $continueTransitionDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        $workflowItem = $checkout->getWorkflowItem();
        $continueTransition = $this->continueTransitionDataProvider->getData($context);

        if ($continueTransition) {
            return $this->getForm($continueTransition, $workflowItem)->createView();
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
