<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

/**
 * Helper logic to start the Checkout workflow.
 */
interface StartCheckoutInterface
{
    public function execute(
        array $sourceCriteria,
        bool $force = false,
        array $data = [],
        array $settings = [],
        bool $showErrors = false,
        bool $forceStartCheckout = false,
        string $startTransition = null,
        bool $validateOnStartCheckout = true
    ): array;
}
