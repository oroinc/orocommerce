<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

/**
 * Adds values for the following fields:
 * * low_inventory_threshold (only when highlight_low_inventory is enabled)
 * * is_upcoming
 * * availability_date
 */
class WebsiteSearchProductIndexerListener
{
    use ContextTrait;

    private EntityFallbackResolver $entityFallbackResolver;
    private UpcomingProductProvider $upcomingProductProvider;

    public function __construct(
        EntityFallbackResolver $entityFallbackResolver,
        UpcomingProductProvider $upcomingProductProvider
    ) {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->upcomingProductProvider = $upcomingProductProvider;
    }

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
                'inv_status',
                $product->getInventoryStatus() ? $product->getInventoryStatus()->getId() : ''
            );

            if ($this->entityFallbackResolver->getFallbackValue($product, 'highlightLowInventory')) {
                $lowInventoryThreshold = $this->entityFallbackResolver->getFallbackValue(
                    $product,
                    'lowInventoryThreshold'
                );
                if (null !== $lowInventoryThreshold) {
                    $event->addField($product->getId(), 'low_inventory_threshold', $lowInventoryThreshold);
                }
            }

            $isUpcoming = $this->upcomingProductProvider->isUpcoming($product);
            if ($isUpcoming) {
                $event->addField($product->getId(), 'is_upcoming', 1);
                $event->addField(
                    $product->getId(),
                    'availability_date',
                    $this->upcomingProductProvider->getAvailabilityDate($product)
                );
            }
        }
    }
}
