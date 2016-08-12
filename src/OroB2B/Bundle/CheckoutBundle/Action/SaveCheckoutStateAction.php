<?php

namespace OroB2B\Bundle\CheckoutBundle\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;

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
    const OPTION_KEY_ENTITY = 'entity';
    const OPTION_KEY_STATE = 'state';
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const OPTION_KEY_TOKEN_VALUE = 'token';

    /** @var array */
    protected $options;

    /** @var CheckoutDiffStorageInterface */
    protected $diffStorage;

    /**
     * {@inheritdoc}
     * @param CheckoutDiffStorageInterface $diffStorage
     */
    public function __construct(ContextAccessor $contextAccessor, CheckoutDiffStorageInterface $diffStorage)
    {
        $this->diffStorage = $diffStorage;
        parent::__construct($contextAccessor);
    }

    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $entityPath = $this->getOption($this->options, self::OPTION_KEY_ENTITY);
        $statePath = $this->getOption($this->options, self::OPTION_KEY_STATE);
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);
        $tokenPath = $this->getOption($this->options, self::OPTION_KEY_TOKEN_VALUE);

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

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_STATE);
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_ENTITY);

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
