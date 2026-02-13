<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\PriceListToProductSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdatedAfter;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles product price changes.
 *
 * Version presence in changeset indicates mass operation, so we should not process individual prices.
 * All prices affected by mass update will be processed in a way like this is done in ImportExportResultListener.
 */
class ProductPriceCPLEntityListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use OptionalListenerTrait;
    use FeatureCheckerHolderTrait;

    protected ExtraActionEntityStorageInterface $extraActionsStorage;
    protected ManagerRegistry $registry;
    protected PriceListTriggerHandler $priceListTriggerHandler;
    protected ShardManager $shardManager;
    protected EventDispatcherInterface $eventDispatcher;
    protected CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler;

    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        ManagerRegistry $registry,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager,
        EventDispatcherInterface $eventDispatcher,
        CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->registry = $registry;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->combinedPriceListBuildTriggerHandler = $combinedPriceListBuildTriggerHandler;
    }

    public function onSave(ProductPriceSaveAfterEvent $event)
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $event->getEventArgs()->getObject();
        $this->addPriceListToProductRelation($productPrice);

        if ($productPrice->getVersion()) {
            return;
        }

        if ($this->isFeaturesEnabled()) {
            $this->combinedPriceListBuildTriggerHandler->handlePriceCreation($productPrice);
        }

        $this->handleChanges($productPrice);
    }

    public function onUpdateAfter(ProductPricesUpdatedAfter $event): void
    {
        if (!$this->enabled) {
            return;
        }
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $changeSets = $event->getChangeSets();
        $saved = array_filter($event->getSaved(), static fn (ProductPrice $price) => null === $price->getVersion());
        $newPrices = array_filter($event->getUpdated(), static function (ProductPrice $price) use ($changeSets) {
            return !array_key_exists($price->getId(), $changeSets)
                || (!empty($changeSets[$price->getId()]['id']) && !isset($changeSets[$price->getId()]['id'][0]));
        });
        $newPrices = array_filter(
            $newPrices,
            static fn (ProductPrice $price) => empty($event->getChangeSets()[$price->getId()]['version'][1])
        );
        $newPrices = array_merge($saved, $newPrices);

        // Single price creation is processed in onSave method. Skip mass save logic to prevent conflicts.
        if (count($newPrices) < 2) {
            return;
        }

        $this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($newPrices);
    }

    public function onRemove(ProductPriceRemove $event)
    {
        $productPrice = $event->getPrice();
        $this->removePriceListToProductRelation($productPrice);
        $this->handleChanges($productPrice);
    }

    protected function handleChanges(ProductPrice $productPrice)
    {
        if (!$this->enabled || !$this->isProductPriceValid($productPrice)) {
            return;
        }
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        // Since there is already a price list check after adding the price, it does not make sense to
        // recalculate the combined price list, as this combined price list may be incomplete.
        if ($this->combinedPriceListBuildTriggerHandler->isSupported($productPrice->getPriceList())) {
            return;
        }

        $this->priceListTriggerHandler->handlePriceListTopic(
            ResolveCombinedPriceByPriceListTopic::getName(),
            $productPrice->getPriceList(),
            [$productPrice->getProduct()]
        );
    }

    protected function addPriceListToProductRelation(ProductPrice $productPrice)
    {
        if (!$this->isProductPriceValid($productPrice)) {
            return;
        }

        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        // create entity to get default value of 'isManual' field
        $relation = new PriceListToProduct();

        /** @var PriceListToProductRepository $repository */
        $repository = $this->getRepository(PriceListToProduct::class);
        $isCreated = $repository->createRelation($priceList, $product, $relation->isManual());

        $relation = $this->findRelation($product, $priceList);
        if ($isCreated && $relation) {
            $this->eventDispatcher->dispatch(
                new PriceListToProductSaveAfterEvent($relation),
                PriceListToProductSaveAfterEvent::NAME
            );
        }
    }

    protected function removePriceListToProductRelation(ProductPrice $productPrice)
    {
        if (!$this->isProductPriceValid($productPrice)) {
            return;
        }

        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        /** @var ProductPriceRepository $repository */
        $repository = $this->getRepository(ProductPrice::class);

        $prices = $repository->findByPriceListAndProductSkus($this->shardManager, $priceList, [$product->getSku()]);
        if (!$prices) {
            /** @var PriceListToProductRepository $repository */
            $repository = $this->getRepository(PriceListToProduct::class);
            $repository->deleteManualRelations($priceList, [$product]);
        }
    }

    /**
     * @param Product $product
     * @param PriceList $priceList
     * @return null|PriceListToProduct
     */
    protected function findRelation(Product $product, PriceList $priceList)
    {
        return $this->getRepository(PriceListToProduct::class)
            ->findOneBy(
                [
                    'product' => $product,
                    'priceList' => $priceList,
                ]
            );
    }

    /**
     * @param ProductPrice $productPrice
     * @return bool
     */
    protected function isProductPriceValid(ProductPrice $productPrice)
    {
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        return $priceList && $product && $priceList->getId() && $product->getId();
    }

    protected function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }
}
