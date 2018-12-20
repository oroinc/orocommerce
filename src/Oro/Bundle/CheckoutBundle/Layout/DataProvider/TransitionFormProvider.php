<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provides form and form view for Checkout transition
 */
class TransitionFormProvider extends AbstractFormProvider
{
    /**
     * @var TransitionProviderInterface
     */
    private $transitionProvider;

    /**
     * @var TransitionProviderInterface
     */
    public function setTransitionProvider(TransitionProviderInterface $transitionProvider)
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

        $cacheKeyOptions = [
            'id' => $workflowItem->getId(),
            'name' => $transition->getName(),
            'workflow_item' => null,
            'form_init' => null,
            'attribute_fields' => null,
        ];

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
     * @return FormView|null
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

        $cacheKeyOptions = [
            'id' => $workflowItem->getId(),
            'name' => $transition->getName(),
            'workflow_item' => null,
            'form_init' => null,
            'attribute_fields' => null,
        ];

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
                'allow_extra_fields' => true,
            ]
        );
    }
}
