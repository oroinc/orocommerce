<?php

namespace Oro\Bundle\ShippingBundle\Condition;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Check if shipping method has shipping rules
 * Usage:
 * @shipping_method_has_shipping_rules: method_identifier
 */
class ShippingMethodHasShippingRules extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'shipping_method_has_shipping_rules';

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * @var ShippingMethodsConfigsRuleRepository
     */
    private $repository;

    /**
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

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
        $methodConfigRules = $this->repository->getRulesByMethod($shippingMethodIdentifier);

        return count($methodConfigRules) !== 0;
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
