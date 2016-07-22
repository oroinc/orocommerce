<?php

namespace OroB2B\Bundle\CheckoutBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;

class CheckCheckoutStates extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'check_checkout_states';

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

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $entity = $this->resolveValue($context, $this->entity);
        $state1 = $this->resolveValue($context, $this->state1);
        $state2 = $this->resolveValue($context, $this->state2);

        return $this->diffManager->isStatesEqual($entity, $state1, $state2);

    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        } elseif (array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }

        if (array_key_exists('state1', $options)) {
            $this->state1 = $options['state1'];
        } elseif (array_key_exists(1, $options)) {
            $this->state1 = $options[1];
        }

        if (array_key_exists('state2', $options)) {
            $this->state2 = $options['state2'];
        } elseif (array_key_exists(2, $options)) {
            $this->state2 = $options[2];
        }

        if (!$this->state1) {
            throw new InvalidArgumentException('Missing "state1" option');
        }

        if (!$this->state2) {
            throw new InvalidArgumentException('Missing "state2" option');
        }

        return $this;
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
        return $this->convertToArray([$this->entity, $this->state1, $this->state2]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity, $this->state1, $this->state2], $factoryAccessor);
    }
}
