<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\LoadTaxBeforeEvent;
use Oro\Bundle\TaxBundle\Manager\TaxManager;

/**
 * Preloads tax values of the order line items before the order tax is loaded
 * to avoid a separate query per line item.
 */
final class PreloadOrderLineItemTaxValuesListener
{
    public function __construct(private readonly TaxManager $taxManager)
    {
    }

    public function onLoadTaxBefore(LoadTaxBeforeEvent $event): void
    {
        $order = $event->getObject();
        if (!$order instanceof Order) {
            return;
        }

        $lineItemIds = [];
        foreach ($order->getLineItems() as $lineItem) {
            $lineItemId = $lineItem->getId();
            if ($lineItemId !== null) {
                $lineItemIds[] = $lineItemId;
            }
        }

        if (!$lineItemIds) {
            return;
        }

        $this->taxManager->preloadTaxValues(OrderLineItem::class, $lineItemIds);
    }
}
