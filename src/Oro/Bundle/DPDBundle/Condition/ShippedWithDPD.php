<?php

namespace Oro\Bundle\DPDBundle\Condition;


use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ShippedWithDPD extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'shipped_with_dpd';

    /**
     * @var DPDShippingMethodProvider
     */
    protected $shippingProvider;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

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
        $shippingMethod = $this->resolveValue($context, $this->propertyPath);
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
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $this->propertyPath = reset($options);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }

}