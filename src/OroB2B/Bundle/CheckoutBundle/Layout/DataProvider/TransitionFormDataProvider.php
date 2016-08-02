<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

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
     * @param TransitionData $transitionData
     *
     * @return FormAccessor
     * @throws WorkflowException
     */
    public function getTransitionForm(WorkflowItem $workflowItem, TransitionData $transitionData)
    {
        $transition = $transitionData->getTransition();

        // in this context parameters used for generating local cache
        $parameters = [$transition->getName(), $workflowItem->getId()];

        return $this->getFormAccessor(
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
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return mixed
     */
    public function getTransitionFormView(WorkflowItem $workflowItem)
    {
        /** @var TransitionData $continueTransitionData */
        $transitionData = $this->transitionDataProvider->getContinueTransition($workflowItem);

        if (!$transitionData || !$transitionData->getTransition()->hasForm()) {
            return null;
        }

        $form = $this->getTransitionForm($workflowItem, $transitionData);

        return $form->getForm()->createView();
    }
}
