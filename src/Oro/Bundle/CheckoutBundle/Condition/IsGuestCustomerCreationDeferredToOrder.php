<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Provider\GuestCustomerCreationConfigProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;

/**
 * Checks if guest customer/customer user creation is deferred to order placement.
 */
class IsGuestCustomerCreationDeferredToOrder extends AbstractCondition
{
    private GuestCustomerCreationConfigProvider $configProvider;

    public function __construct(GuestCustomerCreationConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function isConditionAllowed($context): bool
    {
        return $this->configProvider->isDeferredToOrderCreation();
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
        return 'is_guest_customer_creation_deferred_to_order';
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
