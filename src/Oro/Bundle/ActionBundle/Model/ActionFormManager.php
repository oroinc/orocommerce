<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

class ActionFormManager
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param Action $action
     * @param ActionContext $context
     * @return Form
     */
    public function getActionForm(Action $action, ActionContext $context)
    {
        return $this->formFactory->create(
            $action->getDefinition()->getFormType(),
            $context,
            array_merge(
                $action->getFormOptions($context),
                [
                    'action_context' => $context,
                    'action' => $action
                ]
            )
        );
    }
}
