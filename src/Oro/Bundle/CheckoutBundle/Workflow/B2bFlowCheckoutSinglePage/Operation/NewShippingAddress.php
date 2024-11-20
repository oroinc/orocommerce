<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Operation;

use Oro\Bundle\ActionBundle\Model\AbstractOperationService;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * b2b_flow_checkout_single_page_new_shipping_address operation logic implementation
 */
class NewShippingAddress extends AbstractOperationService
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private UpdateShippingPriceInterface $updateShippingPrice,
        private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter
    ) {
    }

    public function execute(ActionData $data): void
    {
        $checkout = $data->getEntity();
        if (!$checkout instanceof Checkout) {
            throw new WorkflowException('Only Checkout entity is supported');
        }

        $checkout->setSaveShippingAddress($data->offsetGet('save_address'));

        $this->updateShippingPrice->execute($checkout);
        if ($checkout->getShippingCost() === null) {
            $checkout->setShippingMethod(null);
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }

        $this->actionExecutor->executeAction(
            'flush_entity',
            [$checkout->getShippingAddress()]
        );
        $this->actionExecutor->executeAction(
            'flush_entity',
            [$checkout]
        );
    }
}
