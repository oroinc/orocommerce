<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;

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
     * @return Form
     * @throws ActionNotFoundException
     */
    public function getActionForm($actionName)
    {
        $action = $this->actionManager->getAction($actionName);
        if (!$action) {
            throw new ActionNotFoundException($actionName);
        }

        $context = $this->contextHelper->getActionContext();

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
