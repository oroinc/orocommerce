<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Executes start checkout action and sets transision whatever gust or not
 */
class StartCheckoutTransitionAction extends AbstractAction
{
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const TRANSITION_FOR_GUEST = 'start_checkout_as_guest_system';

    /**
     * @var array
     */
    private $options;

    /**
     * @var IsWorkflowStartFromShoppingListAllowed
     */
    private $isWorkflowStartFromShoppingListAllowed;

    /**
     * @param ContextAccessor $contextAccessor
     * @param IsWorkflowStartFromShoppingListAllowed $isWorkflowStartFromShoppingListAllowed
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        IsWorkflowStartFromShoppingListAllowed $isWorkflowStartFromShoppingListAllowed
    ) {
        $this->isWorkflowStartFromShoppingListAllowed = $isWorkflowStartFromShoppingListAllowed;
        parent::__construct($contextAccessor);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $transition = $this->isWorkflowStartFromShoppingListAllowed->isAllowedForGuest() ?
            self::TRANSITION_FOR_GUEST : '';
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $transition);
    }

    /**
     * Initialize action based on passed options.
     *
     * @param array $options
     * @return ActionInterface
     * @throws InvalidParameterException
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        $this->options = $options;

        return $this;
    }
}
