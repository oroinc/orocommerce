<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormView;

use Oro\Component\Layout\DataProvider\AbstractFormDataProvider;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
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
     * @param Checkout $checkout
     * @return null|FormView
     */
    public function getTransitionForm(Checkout $checkout)
    {
        /** @var TransitionData $continueTransitionData */
        $transitionData = $this->transitionDataProvider->getContinueTransition($checkout);

        if (!$transitionData || !$transitionData->getTransition()->hasForm()) {
            return null;
        }

        $workflowItem = $checkout->getWorkflowItem();
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
