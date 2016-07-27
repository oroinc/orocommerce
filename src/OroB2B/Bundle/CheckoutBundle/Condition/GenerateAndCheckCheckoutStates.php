<?php

namespace OroB2B\Bundle\CheckoutBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;

class GenerateAndCheckCheckoutStates extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'generate_and_check_checkout_state';

    const OPTION_KEY_CHECKOUT = 'checkout';
    const OPTION_KEY_TOKEN = 'token';
    const OPTION_KEY_GET_FROM = 'getFrom';
    const OPTION_KEY_STATE = 'state';

    /** @var CheckoutStateDiffManager */
    protected $diffManager;

    /** @var string */
    protected $checkout;

    /** @var string */
    protected $token;

    /** @var string */
    protected $getFrom;

    /** @var string */
    protected $state;

    /**
     * @param CheckoutStateDiffManager $diffManager
     */
    public function __construct(CheckoutStateDiffManager $diffManager)
    {
        $this->diffManager = $diffManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $checkout = $this->resolveValue($context, $this->checkout);
        $token = $this->resolveValue($context, $this->token);
        $getFrom = $this->resolveValue($context, $this->getFrom);
        $state = $this->resolveValue($context, $this->state);

        if (!is_array($getFrom)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Parameter "%s" must have property path to array. "%s" is not array',
                    self::OPTION_KEY_GET_FROM,
                    $getFrom
                )
            );
        }

        if (!array_key_exists($token, $getFrom)) {
            return false;
        }

        $stateFromToken = $getFrom[$token];

        return $this->diffManager->isStatesEqual($checkout, $stateFromToken, $state);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->checkout = $this->getValueFromOption(self::OPTION_KEY_CHECKOUT, $options);
        $this->token = $this->getValueFromOption(self::OPTION_KEY_TOKEN, $options);
        $this->getFrom = $this->getValueFromOption(self::OPTION_KEY_GET_FROM, $options);
        $this->state = $this->getValueFromOption(self::OPTION_KEY_STATE, $options);

        return $this;
    }

    /**
     * @param string $key
     * @param array $options
     * @param bool $required
     * @return mixed
     */
    protected function getValueFromOption($key, $options, $required = true)
    {
        if (!array_key_exists($key, $options)) {
            if (!$required) {
                return null;
            }

            throw new InvalidArgumentException(sprintf('Missing "%s" option', $key));
        }

        return $options[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->checkout, $this->state]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->checkout, $this->state], $factoryAccessor);
    }
}
