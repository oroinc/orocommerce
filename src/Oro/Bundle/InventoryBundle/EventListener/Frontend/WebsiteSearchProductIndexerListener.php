<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

/**
 * Adds values for the following fields:
 * * inventory status
 */
class WebsiteSearchProductIndexerListener
{
    use ContextTrait;

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'inventory')) {
            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();
        foreach ($products as $product) {
            $event->addField(
                $product->getId(),
                'inventory_status',
                $product->getInventoryStatus() ? $product->getInventoryStatus()->getId() : ''
            );
        }
    }
}
