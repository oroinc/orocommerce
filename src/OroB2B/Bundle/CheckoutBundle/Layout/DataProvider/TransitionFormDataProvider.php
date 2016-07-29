<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormView;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Layout\DataProvider\AbstractFormDataProvider;

use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class TransitionFormDataProvider extends AbstractFormDataProvider
{
    /**
     * @var TransitionDataProvider
     */
    protected $transitionDataProvider;

    /**
     * @var object
     */
    public function setTransitionDataProvider($transitionDataProvider)
    {
        $this->transitionDataProvider = $transitionDataProvider;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return null|FormView
     */
    public function getTransitionForm(WorkflowItem $workflowItem)
    {
        /** @var TransitionData $continueTransitionData */
        $transitionData = $this->transitionDataProvider->getContinueTransition($workflowItem);

        if (!$transitionData || !$transitionData->getTransition()->hasForm()) {
            return null;
        }

        $transition = $transitionData->getTransition();

        // in this context parameters used for generating local cache
        $parameters = [$transition->getName(), $workflowItem->getId()];

        $formAccessor = $this->getFormAccessor(
            $transition->getFormType(),
            null,
            $workflowItem->getData(),
            $parameters,
            array_merge(
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName(),
                    'disabled' => !$transitionData->isAllowed()
                ]
            )
        );

        return $formAccessor->getForm()->createView();
    }
}
