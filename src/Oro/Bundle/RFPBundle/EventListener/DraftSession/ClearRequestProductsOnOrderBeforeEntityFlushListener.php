<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\EventListener\DraftSession;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Nulls out the `requestProduct` extended field on every OrderLineItem belonging to the order
 * that is about to be flushed to the database.
 *
 * The `requestProduct` field is an extended field populated during the RFQ-to-order
 * draft creation flow so that the offers form extension can link each OrderLineItem draft back to
 * its source RequestProduct. Once the real order is persisted this link is no longer meaningful
 * and must be cleared to prevent stale foreign-key references in the database.
 */
class ClearRequestProductsOnOrderBeforeEntityFlushListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function onBeforeEntityFlush(AfterFormProcessEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $order = $event->getData();
        if (!$order instanceof Order) {
            return;
        }

        if ($order->getId()) {
            // Existing order is not expected to have line items with requestProduct reference.
            return;
        }

        foreach ($order->getLineItems() as $lineItem) {
            $lineItem->setRequestProduct(null);
        }
    }
}
