<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Method;

/**
 * Interface for a payment method that is aware of the payment method group it is applicable for.
 */
interface PaymentMethodGroupAwareInterface
{
    /**
     * @param string $groupName Payment method group, e.g. storefront_checkout.
     *
     * @return bool True if applicable for the specified payment method group.
     */
    public function isApplicableForGroup(string $groupName): bool;
}
