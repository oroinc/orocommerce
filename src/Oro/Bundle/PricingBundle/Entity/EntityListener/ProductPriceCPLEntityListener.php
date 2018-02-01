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
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param RegistryInterface $registry
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param ShardManager $shardManager
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        RegistryInterface $registry,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->registry = $registry;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
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
        if (!$this->enabled) {
            return;
        }

        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if (!$priceList || !$product || !$priceList->getId() || !$product->getId()) {
            return;
        }
        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_COMBINED_PRICES, $priceList, $product);
    }

    /**
     * @param ProductPrice $productPrice
     */
    protected function addPriceListToProductRelation(ProductPrice $productPrice)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if (null === $this->findRelation($product, $priceList)) {
            $relation = new PriceListToProduct();
            $relation->setPriceList($priceList)
                ->setProduct($product);
            $em = $this->registry->getManagerForClass(PriceListToProduct::class);
            $em->persist($relation);
            $em->flush($relation);
        }
    }

    /**
     * @param ProductPrice $productPrice
     */
    protected function removePriceListToProductRelation(ProductPrice $productPrice)
    {
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if (!$priceList || !$product || !$priceList->getId() || !$product->getId()) {
            return;
        }

        /** @var ProductPriceRepository $repository */
        $repository = $this->getRepository(ProductPrice::class);

        $prices = $repository->findByPriceListAndProductSkus($this->shardManager, $priceList, [$product->getSku()]);
        if (!$prices) {
            /** @var PriceListToProductRepository $repository */
            $repository = $this->getRepository(PriceListToProduct::class);
            $repository->deleteManualRelations($priceList, $product);
        }
    }

    /**
     * @param Product $product
     * @param PriceList $priceList
     * @return null|PriceListToProduct
     */
    protected function findRelation(Product $product, PriceList $priceList)
    {
        $relation = $this->getRepository(PriceListToProduct::class)
            ->findOneBy(
                [
                    'product' => $product,
                    'priceList' => $priceList,
                ]
            );

        return $relation;
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
