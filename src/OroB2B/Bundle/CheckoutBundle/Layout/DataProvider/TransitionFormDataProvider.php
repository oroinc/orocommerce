<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

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

    /**
     * @param DataProviderInterface $continueTransitionDataProvider
     */
    public function setContinueTransitionDataProvider(DataProviderInterface $continueTransitionDataProvider)
    {
        $this->continueTransitionDataProvider = $continueTransitionDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $workflowItem = $context->data()->get('workflowItem');
        /** @var TransitionData $continueTransitionData */
        $transitionData = $this->continueTransitionDataProvider->getData($context);

        if ($transitionData) {
            $form = $this->getForm($transitionData, $workflowItem);
            if ($form) {
                return $form->createView();
            }
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
        if ($transitionData->getTransition()->hasForm()) {
            if (!array_key_exists($key, $this->forms)) {
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
        } else {
            $this->forms[$key] = null;
        }

        return $this->forms[$key];
    }
}
