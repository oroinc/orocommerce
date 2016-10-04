<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProductPriceEntityListener
{
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
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param RegistryInterface $registry
     * @param PriceListTriggerHandler $priceListTriggerHandler
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        RegistryInterface $registry,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->registry = $registry;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * @param ProductPrice $productPrice
     */
    public function prePersist(ProductPrice $productPrice)
    {
        $this->handleChanges($productPrice);
        $this->addPriceListToProductRelation($productPrice);
    }

    /**
     * @param ProductPrice $productPrice
     */
    public function preRemove(ProductPrice $productPrice)
    {
        $this->handleChanges($productPrice);
    }

    /**
     * @param ProductPrice $productPrice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('product') || $event->hasChangedField('priceList')) {
            $this->addPriceListToProductRelation($productPrice);
        }

        $this->handleChanges($productPrice);
    }

    /**
     * @param ProductPrice $productPrice
     */
    protected function handleChanges(ProductPrice $productPrice)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if (!$priceList || !$product || !$priceList->getId() || !$product->getId()) {
            return;
        }
        $this->priceListTriggerHandler->addTriggersForPriceList(Topics::PRICE_LIST_CHANGE, $priceList, $product);
    }

    /**
     * @param ProductPrice $productPrice
     */
    protected function addPriceListToProductRelation(ProductPrice $productPrice)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if ($this->isPriceListToProductScheduled($priceList, $product)) {
            return;
        }

        if (null === $this->findRelation($product, $priceList)) {
            $relation = new PriceListToProduct();
            $relation->setPriceList($priceList)
                ->setProduct($product);
            $this->extraActionsStorage->scheduleForExtraInsert($relation);
        }
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @return bool
     */
    protected function isPriceListToProductScheduled(PriceList $priceList, Product $product)
    {
        /** @var PriceListToProduct[] $scheduledForInsert */
        $scheduledForInsert = $this->extraActionsStorage->getScheduledForInsert(
            PriceListToProduct::class
        );

        foreach ($scheduledForInsert as $scheduled) {
            if ($scheduled->getProduct()->getId() === $product->getId()
                && $scheduled->getPriceList()->getId() === $priceList->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Product $product
     * @param PriceList $priceList
     * @return null|PriceListToProduct
     */
    protected function findRelation(Product $product, PriceList $priceList)
    {
        $relation = $this->registry->getManagerForClass(PriceListToProduct::class)
            ->getRepository(PriceListToProduct::class)
            ->findOneBy(
                [
                    'product' => $product,
                    'priceList' => $priceList,
                ]
            );

        return $relation;
    }
}
