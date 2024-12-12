<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\Provider\CheckoutThemeBCProvider;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provides form and form view for Checkout transition
 */
class TransitionFormProvider extends AbstractFormProvider
{
    private ?TransitionProviderInterface $transitionProvider = null;
    private ?CheckoutThemeBCProvider $checkoutThemeBCProvider = null;

    /**
     * @var TransitionProviderInterface
     */
    public function setTransitionProvider(TransitionProviderInterface $transitionProvider)
    {
        $this->transitionProvider = $transitionProvider;
    }

    public function setThemeBCProvider(CheckoutThemeBCProvider $checkoutThemeBCProvider): self
    {
        $this->checkoutThemeBCProvider = $checkoutThemeBCProvider;

        return $this;
    }

    /**
     * @throws WorkflowException
     */
    public function getTransitionFormByTransition(WorkflowItem $workflowItem, Transition $transition): ?FormInterface
    {
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
            $this->getFormOptions($workflowItem, $transition),
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
            $this->getFormOptions($workflowItem, $transition),
            $cacheKeyOptions
        );
    }

    private function getFormOptions(WorkflowItem $workflowItem, Transition $transition): array
    {
        $defaultOptions = [
            'workflow_item' => $workflowItem,
            'transition_name' => $transition->getName(),
            'allow_extra_fields' => true
        ];

        /**
         * Decide if the _token field is available on the checkout form, depending on the theme.
         * This is the BC layer for the checkout form.
         */
        if (!$this->checkoutThemeBCProvider->isOldtheme())  {
            $defaultOptions['csrf_protection'] = false;
        }

        return array_merge($transition->getFormOptions(), $defaultOptions);
    }
}
