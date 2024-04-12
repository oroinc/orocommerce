<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

/**
 * Find existing Checkout or create a new one and start checkout workflow.
 */
interface FindOrCreateCheckoutInterface
{
    public function execute(
        array $sourceCriteria,
        array $checkoutData = [],
        bool $updateData = false,
        bool $forceStartCheckout = false,
        string $startTransition = null
    ): array;
}
