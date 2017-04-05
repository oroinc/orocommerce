<?php

namespace Oro\Bundle\ShippingBundle\Condition;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Check if shipping method has shipping rules
 * Usage:
 * @shipping_method_has_shipping_rules: method_identifier
 */
abstract class AbstractShippingMethodHasShippingRules extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'shipping_method_has_shipping_rules';

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        $option = reset($options);
        $this->propertyPath = $option;

        if (!$this->propertyPath) {
            throw new \InvalidArgumentException('Missing "method_identifier" option');
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function isConditionAllowed($context)
    {
        $shippingMethodIdentifier = $this->resolveValue($context, $this->propertyPath, false);
        $methodConfigRules = $this->getRulesByMethod($shippingMethodIdentifier);

        return count($methodConfigRules) !== 0;
    }

    /**
     * @param $shippingMethodIdentifier
     *
     * @return ShippingMethodsConfigsRule[]
     */
    abstract protected function getRulesByMethod($shippingMethodIdentifier);

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    /**
     * {@inheritDoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }
}
