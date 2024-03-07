<?php

namespace Oro\Bundle\ShippingBundle\Condition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

/**
 * Checks if shipping method has enabled shipping rules.
 * Usage:
 * @shipping_method_has_enabled_shipping_rules: method_identifier
 */
class ShippingMethodHasEnabledShippingRules extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private ManagerRegistry $doctrine;
    protected mixed $propertyPath = null;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    protected function isConditionAllowed($context)
    {
        return (bool)$this->doctrine->getRepository(ShippingMethodsConfigsRule::class)
            ->getEnabledRulesByMethod($this->resolveValue($context, $this->propertyPath, false));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'shipping_method_has_enabled_shipping_rules';
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        $this->propertyPath = reset($options);
        if (!$this->propertyPath) {
            throw new \InvalidArgumentException('Missing "method_identifier" option');
        }

        return $this;
    }

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
