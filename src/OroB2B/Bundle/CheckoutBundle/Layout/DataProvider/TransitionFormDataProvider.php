<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

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
     * @var FormInterface[]
     */
    protected $forms = [];

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
        /** @var TransitionData $continueTransitionData */
        $transitionData = $this->continueTransitionDataProvider->getData($context);

        if ($transitionData) {
            return $this->getForm($transitionData, $workflowItem)->createView();
        }

        return null;
    }

    /**
     * @param TransitionData $transitionData
     * @param WorkflowItem $workflowItem
     * @return FormInterface
     */
    public function getForm(TransitionData $transitionData, WorkflowItem $workflowItem)
    {
        $key = $transitionData->getTransition()->getName() . ':' . $workflowItem->getId();
        if (!array_key_exists($key, $this->forms) && $transitionData->getTransition()->hasForm()) {
            $transition = $transitionData->getTransition();

            $this->forms[$key] = $this->formFactory->create(
                $transition->getFormType(),
                $workflowItem->getData(),
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

        return $this->forms[$key];
    }
}
