<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Condition;

use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorage;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Compares stored by token checkout state with current checkout state.
 *
 * Usage:
 *
 * @is_checkout_state_valid:
 *      entity: $checkout
 *      token: $.result.stateToken
 *      current_state: $.result.currentCheckoutState
 */
class IsCheckoutStateValid extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private const OPTION_ENTITY = 'entity';
    private const OPTION_TOKEN = 'token';
    private const OPTION_CURRENT_STATE = 'current_state';

    /** @var CheckoutStateDiffManager */
    private $checkoutDiffManager;

    /** @var CheckoutDiffStorage */
    private $checkoutDiffStorage;

    /** @var string */
    private $entity;

    /** @var string */
    private $savedStateToken;

    /** @var array */
    private $currentState;

    public function __construct(
        CheckoutStateDiffManager $checkoutStateDiffManager,
        CheckoutDiffStorage $checkoutDiffStorage
    ) {
        $this->checkoutDiffManager = $checkoutStateDiffManager;
        $this->checkoutDiffStorage = $checkoutDiffStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context): bool
    {
        $entity = $this->resolveValue($context, $this->entity);
        $savedStateToken = $this->resolveValue($context, $this->savedStateToken);
        $currentState = $this->resolveValue($context, $this->currentState);
        $savedState = $this->checkoutDiffStorage->getState($entity, $savedStateToken);
        $result = true;
        if ($savedState && $currentState) {
            $result = $this->checkoutDiffManager->isStatesEqual($entity, $savedState, $currentState);
            if (!$result) {
                $this->checkoutDiffStorage->deleteStates($entity, $savedStateToken);
                $this->checkoutDiffStorage->addState($entity, $currentState, ['token' => $savedStateToken]);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options): self
    {
        $this->entity = $this->getValueFromOption($options, self::OPTION_ENTITY);
        $this->savedStateToken = $this->getValueFromOption($options, self::OPTION_TOKEN);
        $this->currentState = $this->getValueFromOption($options, self::OPTION_CURRENT_STATE);

        return $this;
    }

    /**
     * @param array $options
     * @param string $key
     * @return string
     */
    private function getValueFromOption($options, $key)
    {
        if (!array_key_exists($key, $options)) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', $key));
        }

        return $options[$key];
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'is_checkout_state_valid';
    }

    /** {@inheritdoc} */
    public function toArray(): array
    {
        return $this->convertToArray([$this->entity, $this->savedStateToken, $this->currentState]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor): string
    {
        return $this->convertToPhpCode([$this->entity, $this->savedStateToken, $this->currentState], $factoryAccessor);
    }
}
