<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Recalculates the checkout shipping cost.
 */
class RecalculateCheckoutShippingCost implements ProcessorInterface
{
    private const PROCESSED_CHECKOUTS = 'recalculated_checkout_shipping_costs';

    public function __construct(
        private readonly ShippingMethodActionsInterface $shippingMethodActions
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $entity = $context->getData();
        if ($entity instanceof Checkout) {
            $this->recalculateShippingCost($entity, $context);
        } elseif ($entity instanceof CheckoutLineItem) {
            $checkout = $entity->getCheckout();
            if (null !== $checkout) {
                $this->recalculateShippingCost($checkout, $context);
            }
        } elseif ($entity instanceof CheckoutProductKitItemLineItem) {
            $checkout = $entity->getLineItem()?->getCheckout();
            if (null !== $checkout) {
                $this->recalculateShippingCost($checkout, $context);
            }
        }
    }

    private function recalculateShippingCost(Checkout $checkout, CustomizeFormDataContext $context): void
    {
        $sharedData = $context->getSharedData();
        $processedCheckouts = $sharedData->get(self::PROCESSED_CHECKOUTS) ?? [];
        $checkoutHash = spl_object_hash($checkout);
        if (!isset($processedCheckouts[$checkoutHash])) {
            $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
            $processedCheckouts[$checkoutHash] = true;
            $sharedData->set(self::PROCESSED_CHECKOUTS, $processedCheckouts);
        }
    }
}
