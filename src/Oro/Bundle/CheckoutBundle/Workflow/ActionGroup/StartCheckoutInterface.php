<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

/**
 * Helper logic to start the Checkout workflow.
 */
interface StartCheckoutInterface
{
    /**
     * @param array $sourceCriteria
     * @param bool $force
     * @param array $data
     * @param array $settings
     * @param bool $showErrors
     * @param bool $forceStartCheckout
     * @param string|null $startTransition
     * @param bool $validateOnStartCheckout
     * @return array{
     *     checkout: \Oro\Bundle\CheckoutBundle\Entity\Checkout,
     *     workflowItem: \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem,
     *     redirectUrl?: string,
     *     errors?: \Doctrine\Common\Collections\Collection|array
     * }
     */
    public function execute(
        array   $sourceCriteria,
        bool    $force = false,
        array   $data = [],
        array   $settings = [],
        bool    $showErrors = false,
        bool    $forceStartCheckout = false,
        ?string $startTransition = null,
        bool    $validateOnStartCheckout = true
    ): array;
}
