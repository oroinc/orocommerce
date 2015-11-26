<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

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
        return $this->createForm(
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

    /**
     * @param string|FormTypeInterface $type
     * @param mixed $data
     * @param array $options
     * @return FormInterface
     */
    protected function createForm($type, $data = null, array $options = [])
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * @param ActionContext $context
     * @return bool
     */
    public function hasForm(ActionContext $context)
    {
        $formOptions = $this->getFormOptions($context);

        return !empty($formOptions) && !empty($formOptions['attribute_fields']);
    }
}
