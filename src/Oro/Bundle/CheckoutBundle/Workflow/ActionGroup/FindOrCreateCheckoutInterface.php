<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

/**
 * Find existing Checkout or create a new one and start checkout workflow.
 */
interface FindOrCreateCheckoutInterface
{
    /**
     * @param array $sourceCriteria
     * @param array $checkoutData
     * @param bool $updateData
     * @param bool $forceStartCheckout
     * @param string|null $startTransition
     * @return array{
     *     checkout: \Oro\Bundle\CheckoutBundle\Entity\Checkout,
     *     workflowItem: \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem,
     *     updateData: bool
     * }
     */
    public function execute(
        array $sourceCriteria,
        array $checkoutData = [],
        bool $updateData = false,
        bool $forceStartCheckout = false,
        string $startTransition = null
    ): array;
}
