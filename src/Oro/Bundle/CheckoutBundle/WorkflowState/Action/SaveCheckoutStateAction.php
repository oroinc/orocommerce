<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Save checkout state to the storage
 *
 * Usage:
 * @save_checkout_state:
 *      entity: $checkout
 *      state: $.result.actual_checkout_state
 *      attribute: $actual_token    # Optional. Generated token will be written to this attribute
 *      token: $token               # Optional. This value will be used as a token
 */
class SaveCheckoutStateAction extends AbstractAction
{
    public const OPTION_KEY_ENTITY = 'entity';
    public const OPTION_KEY_STATE = 'state';
    public const OPTION_KEY_ATTRIBUTE = 'attribute';
    public const OPTION_KEY_TOKEN = 'token';

    /** @var array */
    protected $options;

    /** @var CheckoutDiffStorageInterface */
    protected $diffStorage;

    public function __construct(ContextAccessor $contextAccessor, CheckoutDiffStorageInterface $diffStorage)
    {
        $this->diffStorage = $diffStorage;
        parent::__construct($contextAccessor);
    }

    #[\Override]
    protected function executeAction($context)
    {
        $entityPath = $this->getOption($this->options, self::OPTION_KEY_ENTITY);
        $statePath = $this->getOption($this->options, self::OPTION_KEY_STATE);
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);
        $tokenPath = $this->getOption($this->options, self::OPTION_KEY_TOKEN);

        $entity = $this->contextAccessor->getValue($context, $entityPath);
        $state = $this->contextAccessor->getValue($context, $statePath);

        $options = [];
        if ($tokenPath) {
            $token = $this->contextAccessor->getValue($context, $tokenPath);
            $options['token'] = $token;
        }

        $token = $this->diffStorage->addState($entity, $state, $options);

        if ($attributePath) {
            $this->contextAccessor->setValue($context, $attributePath, $token);
        }
    }

    #[\Override]
    public function initialize(array $options)
    {
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_ENTITY);
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_STATE);

        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     * @param string $parameter
     * @throws InvalidParameterException
     */
    protected function throwExceptionIfRequiredParameterEmpty($options, $parameter)
    {
        if (empty($options[$parameter])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', $parameter));
        }
    }
}
