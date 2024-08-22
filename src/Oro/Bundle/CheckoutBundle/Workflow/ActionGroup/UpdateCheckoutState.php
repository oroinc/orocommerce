<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;

/**
 * Updates stored checkout state.
 */
class UpdateCheckoutState implements UpdateCheckoutStateInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private CheckoutDiffStorageInterface $diffStorage,
        private CheckoutStateDiffManager $diffManager
    ) {
    }

    public function execute(
        Checkout $checkout,
        string $stateToken,
        ?bool $updateCheckoutState = false,
        ?bool $forceUpdate = false
    ): bool {
        $isSupportedRequest = $this->actionExecutor->evaluateExpression(
            'check_request',
            [
                'expected_key' => 'update_checkout_state',
                'expected_value' => 1
            ]
        );
        if ($isSupportedRequest) {
            $updateCheckoutState = true;
        }

        $savedCheckoutState = [];
        if (!$updateCheckoutState) {
            $savedCheckoutState = $this->diffStorage->getState($checkout, $stateToken);
        }

        if ($forceUpdate || $updateCheckoutState || empty($savedCheckoutState)) {
            $this->diffStorage->deleteStates($checkout, $stateToken);
            $currentCheckoutState = $this->diffManager->getCurrentState($checkout);
            $this->diffStorage->addState($checkout, $currentCheckoutState, ['token' => $stateToken]);
            $updateCheckoutState = false;
        }

        return (bool)$updateCheckoutState;
    }
}
