<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\PriceListToProductSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductPriceCPLEntityListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param RegistryInterface $registry
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param ShardManager $shardManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        RegistryInterface $registry,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->registry = $registry;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProductPriceSaveAfterEvent $event
     */
    public function onSave(ProductPriceSaveAfterEvent $event)
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $event->getEventArgs()->getEntity();
        $this->addPriceListToProductRelation($productPrice);
        $this->handleChanges($productPrice);
    }

    /**
     * @param ProductPriceRemove $event
     */
    public function onRemove(ProductPriceRemove $event)
    {
        $productPrice = $event->getPrice();
        $this->removePriceListToProductRelation($productPrice);
        $this->handleChanges($productPrice);
    }

    /**
     * @param ProductPrice $productPrice
     */
    protected function handleChanges(ProductPrice $productPrice)
    {
        if (!$this->enabled || !$this->isProductPriceValid($productPrice)) {
            return;
        }

        $this->priceListTriggerHandler->addTriggerForPriceList(
            Topics::RESOLVE_COMBINED_PRICES,
            $productPrice->getPriceList(),
            [$productPrice->getProduct()]
        );
    }

    /**
     * @param ProductPrice $productPrice
     */
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
                PriceListToProductSaveAfterEvent::NAME,
                new PriceListToProductSaveAfterEvent($relation)
            );
        }
    }

    /**
     * @param ProductPrice $productPrice
     */
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

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }
}
