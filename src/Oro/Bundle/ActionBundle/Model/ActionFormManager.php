<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

class ActionFormManager
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var ActionManager */
    protected $actionManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ActionManager $actionManager,
        ContextHelper $contextHelper
    ) {
        $this->formFactory = $formFactory;
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
    }

    /**
     * @param string $actionName
     * @param ActionData $context
     * @return Form
     */
    public function getActionForm($actionName, ActionData $context)
    {
        $action = $this->actionManager->getAction($actionName, $context);

        return $this->formFactory->create(
            $action->getDefinition()->getFormType(),
            $context,
            array_merge($action->getFormOptions($context), ['action' => $action])
        );
    }
}
