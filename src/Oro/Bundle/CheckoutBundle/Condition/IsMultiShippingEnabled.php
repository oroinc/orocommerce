<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

/**
 * Workflow condition used to check if multiple shipping functionality is enabled.
 */
class IsMultiShippingEnabled extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private const CONDITION_NAME = 'is_multishipping_enabled';

    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    protected function isConditionAllowed($context): bool
    {
        return $this->configProvider->isShippingSelectionByLineItemEnabled();
    }

    public function initialize(array $options): self
    {
        return $this;
    }

    public function getName()
    {
        return self::CONDITION_NAME;
    }

    public function toArray()
    {
        return $this->convertToArray([]);
    }

    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([], $factoryAccessor);
    }
}
