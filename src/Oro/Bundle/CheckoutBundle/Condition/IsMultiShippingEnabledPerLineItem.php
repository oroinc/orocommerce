<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;

/**
 * Checks if a shipping method should be selected for each line item.
 */
class IsMultiShippingEnabledPerLineItem extends AbstractCondition
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
        return $this->configProvider->isShippingSelectionByLineItemEnabled();
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
        return 'is_multishipping_enabled_per_line_item';
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
