<?php

namespace OroB2B\Bundle\CheckoutBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;

/**
 * Compare checkout states
 *
 * Usage:
 * @check_checkout_states:
 *      entity: $checkout
 *      state1: $.result.old_checkout_state
 *      state2: $.result.new_checkout_state
 */
class CheckCheckoutStates extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'check_checkout_states';

    const OPTION_KEY_ENTITY = 'entity';
    const OPTION_KEY_STATE1 = 'state1';
    const OPTION_KEY_STATE2 = 'state2';

    /** @var CheckoutStateDiffManager */
    protected $diffManager;

    /** @var string */
    protected $entity;

    /** @var string */
    protected $state1;

    /** @var string */
    protected $state2;

    /**
     * @param CheckoutStateDiffManager $diffManager
     */
    public function __construct(CheckoutStateDiffManager $diffManager)
    {
        $this->diffManager = $diffManager;
    }

    /** {@inheritdoc} */
    protected function isConditionAllowed($context)
    {
        $checkout = $this->resolveValue($context, $this->entity);
        $state1 = $this->resolveValue($context, $this->state1);
        $state2 = $this->resolveValue($context, $this->state2);

        return $this->diffManager->isStatesEqual($checkout, $state1, $state2);
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        $this->entity = $this->getValueFromOption(self::OPTION_KEY_ENTITY, $options);
        $this->state1 = $this->getValueFromOption(self::OPTION_KEY_STATE1, $options);
        $this->state2 = $this->getValueFromOption(self::OPTION_KEY_STATE2, $options);

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
//
//    /** {@inheritdoc} */
//    protected function getMessage()
//    {
//        $message = parent::getMessage();
//
//        if ($message === null) {
//            // @todo: Move it to translations
//            $message = 'There was a change to the contents of your order.';
//        }
//
//        return $message;
//    }


    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function toArray()
    {
        return $this->convertToArray([$this->entity, $this->state1, $this->state2]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity, $this->state1, $this->state2], $factoryAccessor);
    }
}
