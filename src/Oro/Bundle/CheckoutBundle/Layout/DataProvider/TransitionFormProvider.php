<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

class TransitionFormProvider extends AbstractFormProvider
{
    /**
     * @var TransitionProvider
     */
    protected $transitionProvider;

    /**
     * @var object
     */
    public function setTransitionProvider($transitionProvider)
    {
        $this->transitionProvider = $transitionProvider;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param TransitionData $transitionData
     *
     * @return FormInterface|null
     * @throws WorkflowException
     */
    public function getTransitionForm(WorkflowItem $workflowItem, TransitionData $transitionData)
    {
        $transition = $transitionData->getTransition();

        // in this context parameters used for generating local cache
        $parameters = [$transition->getName(), $workflowItem->getId()];

        if (!$transition->hasForm()) {
            return null;
        }

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
                    'disabled' => !$transitionData->isAllowed(),
                    'allow_extra_fields' => true,
                ]
            )
        )->getForm();
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return mixed
     */
    public function getTransitionFormView(WorkflowItem $workflowItem)
    {
        /** @var TransitionData $continueTransitionData */
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem);

        if (!$transitionData) {
            return null;
        }

        $form = $this->getTransitionForm($workflowItem, $transitionData);

        return $form ? $form->createView() : null;
    }
}
