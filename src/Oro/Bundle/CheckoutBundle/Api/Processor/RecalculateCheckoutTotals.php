<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Recalculates the checkout totals.
 */
class RecalculateCheckoutTotals implements ProcessorInterface
{
    private const PROCESSED_CHECKOUTS = 'recalculated_checkout_totals';

    public function __construct(
        private readonly CheckoutSubtotalUpdater $checkoutSubtotalUpdater
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $entity = $context->getData();
        if ($entity instanceof Checkout) {
            $this->recalculateTotals($entity, $context, false);
        } elseif ($entity instanceof CheckoutLineItem) {
            $checkout = $entity->getCheckout();
            if (null !== $checkout) {
                $this->recalculateTotals($checkout, $context, true);
            }
        } elseif ($entity instanceof CheckoutProductKitItemLineItem) {
            $checkout = $entity->getLineItem()?->getCheckout();
            if (null !== $checkout) {
                $this->recalculateTotals($checkout, $context, true);
            }
        }
    }

    private function recalculateTotals(
        Checkout $checkout,
        CustomizeFormDataContext $context,
        bool $forceLoadLineItems
    ): void {
        $sharedData = $context->getSharedData();
        $processedCheckouts = $sharedData->get(self::PROCESSED_CHECKOUTS) ?? [];
        $checkoutHash = spl_object_hash($checkout);
        if (!isset($processedCheckouts[$checkoutHash])) {
            if ($this->isTotalsRecalculationRequired($checkout, $context, $forceLoadLineItems)) {
                $this->checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout);
            }
            $processedCheckouts[$checkoutHash] = true;
            $sharedData->set(self::PROCESSED_CHECKOUTS, $processedCheckouts);
        }
    }

    private function isTotalsRecalculationRequired(
        Checkout $checkout,
        CustomizeFormDataContext $context,
        bool $forceLoadLineItems
    ): bool {
        return
            $this->isEntityValid($checkout, $context)
            && $this->isLineItemsValid($checkout->getLineItems(), $context, $forceLoadLineItems);
    }

    private function isEntityValid(object $entity, CustomizeFormDataContext $context): bool
    {
        $form = $context->findForm($entity);

        return null === $form || $form->isValid();
    }

    private function isLineItemsValid(
        Collection $lineItems,
        CustomizeFormDataContext $context,
        bool $forceLoadLineItems
    ): bool {
        if (!$forceLoadLineItems && $lineItems instanceof PersistentCollection && !$lineItems->isInitialized()) {
            return false;
        }

        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$this->isEntityValid($lineItem, $context)) {
                return false;
            }
            if (!$this->isKitItemLineItemsValid($lineItem->getKitItemLineItems(), $context)) {
                return false;
            }
        }

        return true;
    }

    private function isKitItemLineItemsValid(Collection $kitItemLineItems, CustomizeFormDataContext $context): bool
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        foreach ($kitItemLineItems as $kitItemLineItem) {
            if (!$this->isEntityValid($kitItemLineItem, $context)) {
                return false;
            }
        }

        return true;
    }
}
