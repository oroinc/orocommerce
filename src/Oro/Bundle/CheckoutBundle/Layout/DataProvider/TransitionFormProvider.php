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
    private $transitionProvider;

    /**
     * @var TransitionProvider
     */
    public function setTransitionProvider(TransitionProvider $transitionProvider)
    {
        $this->transitionProvider = $transitionProvider;
    }

    /**
     * @param WorkflowItem   $workflowItem
     * @param TransitionData $transitionData
     *
     * @return FormInterface|null
     * @throws WorkflowException
     */
    public function getTransitionForm(WorkflowItem $workflowItem, TransitionData $transitionData)
    {
        $transition = $transitionData->getTransition();
        if (!$transition->hasForm()) {
            return null;
        }

        $cacheKeyOptions = ['id' => $workflowItem->getId(), 'name' => $transition->getName()];

        return $this->getForm(
            $transition->getFormType(),
            $workflowItem->getData(),
            $this->getFormOptions($workflowItem, $transitionData),
            $cacheKeyOptions
        );
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return mixed
     */
    public function getTransitionFormView(WorkflowItem $workflowItem)
    {
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem);
        if (!$transitionData) {
            return null;
        }

        $transition = $transitionData->getTransition();
        if (!$transitionData->getTransition()->hasForm()) {
            return null;
        }

        $cacheKeyOptions = ['id' => $workflowItem->getId(), 'name' => $transition->getName()];

        return $this->getFormView(
            $transition->getFormType(),
            $workflowItem->getData(),
            $this->getFormOptions($workflowItem, $transitionData),
            $cacheKeyOptions
        );
    }

    /**
     * @param WorkflowItem   $workflowItem
     * @param TransitionData $transitionData
     *
     * @return array
     */
    private function getFormOptions(WorkflowItem $workflowItem, TransitionData $transitionData)
    {
        $transition = $transitionData->getTransition();

        return array_merge(
            $transition->getFormOptions(),
            [
                'workflow_item' => $workflowItem,
                'transition_name' => $transition->getName(),
                'disabled' => !$transitionData->isAllowed(),
                'allow_extra_fields' => true,
            ]
        );
    }
}
