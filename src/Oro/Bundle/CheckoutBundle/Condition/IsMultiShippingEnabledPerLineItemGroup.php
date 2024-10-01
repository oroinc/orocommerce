<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;

/**
 * Checks if a shipping method should be selected for each group of line items.
 */
class IsMultiShippingEnabledPerLineItemGroup extends AbstractCondition
{
    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    #[\Override]
    protected function isConditionAllowed($context): bool
    {
        return
            $this->configProvider->isLineItemsGroupingEnabled()
            && !$this->configProvider->isShippingSelectionByLineItemEnabled();
    }

    #[\Override]
    public function initialize(array $options): self
    {
        return $this;
    }

    #[\Override]
    public function getName()
    {
        return 'is_multishipping_enabled_per_line_item_group';
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray([]);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([], $factoryAccessor);
    }
}
