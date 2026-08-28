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
 *
 * A versioned mass operation (import / batch API) stamps the version on the price entity.
 * For updates this appears as a version change in the Doctrine changeset; for new inserts the
 * version is set on the entity with an empty changeset.
 * In both cases per-price reindexation is skipped and bulk processing is delegated to
 * ImportExportResultListener::postPersist (import) and AfterSaveMqJobListener::onAfterSave (batch API),
 * both of which end up in ResolveVersionedFlatPriceTopic.
 */
class ProductPriceFlatEntityListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use OptionalListenerTrait;
    use FeatureCheckerHolderTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onSave(ProductPriceSaveAfterEvent $event): void
    {
        $args = $event->getEventArgs();
        /** @var ProductPrice $productPrice */
        $productPrice = $args->getObject();

        if ($args->getEntityChangeSet()) {
            // Skip per-price processing only when the version was changed within this
            // versioned mass operation (import / batch API). A normal edit of an already-versioned
            // row does not change the version field and must be processed normally.
            if ($args->hasChangedField('version') && $args->getNewValue('version') !== null) {
                return;
            }
        } elseif ($productPrice->getVersion()) {
            // New price created within a versioned mass operation (import / batch API).
            // It is reindexed in bulk by ResolveVersionedFlatPriceTopic,
            // so per-price processing is skipped here to avoid MQ flooding.
            return;
        }

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
