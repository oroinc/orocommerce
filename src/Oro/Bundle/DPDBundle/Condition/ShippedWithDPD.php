<?php

namespace Oro\Bundle\DPDBundle\Condition;


use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

class ShippedWithDPD extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'shipped_with_dpd';

    /**
     * @var DPDShippingMethodProvider
     */
    protected $shippingProvider;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param DPDShippingMethodProvider $shippingProvider
     */
    public function __construct(DPDShippingMethodProvider $shippingProvider)
    {
        $this->shippingProvider = $shippingProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $shippingMethod = $this->resolveValue($context, $this->value);
        return $this->shippingProvider->hasShippingMethod($shippingMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->value, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ value }}' => $this->resolveValue($context, $this->value)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $this->value = reset($options);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }

}