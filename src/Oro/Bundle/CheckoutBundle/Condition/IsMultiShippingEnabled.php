<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;

/**
 * Checks if a shipping method should be selected for each line item or for each group of line items.
 */
class IsMultiShippingEnabled extends AbstractCondition
{
    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function isConditionAllowed($context): bool
    {
        return $this->configProvider->isMultiShippingEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options): self
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'is_multishipping_enabled';
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->convertToArray([]);
    }

    /**
     * {@inheritDoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([], $factoryAccessor);
    }
}
