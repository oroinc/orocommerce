<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Util\ClassUtils as DoctrineClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use Oro\Bundle\PricingBundle\Event\ProductPriceChange;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductPriceEntityListener
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @var  EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param EventDispatcherInterface $eventDispatcher
     * @param RegistryInterface $registry
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        EventDispatcherInterface $eventDispatcher,
        RegistryInterface $registry
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->registry = $registry;
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
        $trigger = $this->createProductPriceChangeTrigger($productPrice);

        if (null === $trigger || $this->isExistingTrigger($trigger)) {
            return;
        }

        $this->eventDispatcher->dispatch(ProductPriceChange::NAME, new ProductPriceChange());
        $this->extraActionsStorage->scheduleForExtraInsert($trigger);
    }

    /**
     * @param ProductPriceChangeTrigger $trigger
     * @return bool
     */
    protected function isExistingTrigger(ProductPriceChangeTrigger $trigger)
    {
        /** @var ProductPriceChangeTrigger[] $scheduledForInsert */
        $scheduledForInsert = $this->extraActionsStorage
            ->getScheduledForInsert(DoctrineClassUtils::getClass($trigger));

        foreach ($scheduledForInsert as $scheduledTrigger) {
            if ($scheduledTrigger->getPriceList()->getId() === $trigger->getPriceList()->getId()
                && $scheduledTrigger->getProduct()->getId() === $trigger->getProduct()->getId()
            ) {
                return true;
            }
        }

        return $this->getRepository()->isExisting($trigger);
    }

    /**
     * @param ProductPrice $productPrice
     * @return ProductPriceChangeTrigger|null
     */
    protected function createProductPriceChangeTrigger(ProductPrice $productPrice)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if (!$priceList || !$product || !$priceList->getId() || !$product->getId()) {
            return null;
        }

        return new ProductPriceChangeTrigger($priceList, $product);
    }

    /**
     * @return ProductPriceChangeTriggerRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->registry
                ->getManagerForClass(ProductPriceChangeTrigger::class)
                ->getRepository(ProductPriceChangeTrigger::class);
        }

        return $this->repository;
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
