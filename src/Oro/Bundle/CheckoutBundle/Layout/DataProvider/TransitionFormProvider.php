<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

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
     * @param array   $options
     *
     * @return FormInterface|null
     * @throws WorkflowException
     */
    public function getTransitionForm(WorkflowItem $workflowItem, TransitionData $transitionData, array $options = [])
    {
        $transition = $transitionData->getTransition();

        if (!$transition->hasForm()) {
            return null;
        }

        $options[AbstractFormProvider::USED_FOR_CACHE_ONLY_OPTION] = [$transition->getName(), $workflowItem->getId()];

        return $this->getForm(
            $transition->getFormType(),
            $workflowItem->getData(),
            array_merge(
                $options,
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName(),
                    'disabled' => !$transitionData->isAllowed(),
                    'allow_extra_fields' => true,
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
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem);

        if (!$transitionData) {
            return null;
        }

        $form = $this->getTransitionForm($workflowItem, $transitionData);

        return $form ? $form->createView() : null;
    }
}
