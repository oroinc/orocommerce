<?php

namespace OroB2B\Bundle\CheckoutBundle\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;

/**
 * Delete checkout state from the storage
 *
 * Usage:
 * @delete_checkout_state:
 *      entity: $checkout
 *      token: $token
 */
class DeleteCheckoutStateAction extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';
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
        $tokenPath = $this->getOption($this->options, self::OPTION_KEY_TOKEN_VALUE);

        $entity = $this->contextAccessor->getValue($context, $entityPath);

        $token = null;
        if ($tokenPath) {
            $token = $this->contextAccessor->getValue($context, $tokenPath);
        }

        $this->diffStorage->deleteStates($entity, $token);
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
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
