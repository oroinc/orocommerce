<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Sends a request to re-index a concrete product when the product prices is changed.
 */
class ProductPriceFlatEntityListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use OptionalListenerTrait, FeatureCheckerHolderTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onSave(ProductPriceSaveAfterEvent $event): void
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $event->getEventArgs()->getEntity();
        $this->handleChanges($productPrice);
    }

    public function onRemove(ProductPriceRemove $event): void
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $event->getPrice();
        $this->handleChanges($productPrice);
    }

    protected function handleChanges(ProductPrice $productPrice): void
    {
        if (!$this->enabled || !$this->isFeaturesEnabled()) {
            return;
        }

        $product = $productPrice->getProduct();
        $event = new ReindexationRequestEvent([Product::class], [], [$product->getId()], true, ['pricing']);
        $this->eventDispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }
}
