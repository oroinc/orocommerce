<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Event\ProductPriceChange;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;

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
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function prePersist(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->handleChanges($productPrice, $event);
        $this->addPriceListToProductRelation($productPrice, $event->getEntityManager());
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function preRemove(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->handleChanges($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($productPrice);
        if ($this->isProductChanged($changeSet) || $this->isPriceListChanged($changeSet)) {
            $this->addPriceListToProductRelation($productPrice, $entityManager);
        }

        $this->handleChanges($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    protected function handleChanges(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $trigger = $this->createProductPriceChangeTrigger($productPrice);

        $em = $event->getEntityManager();
        if (null === $trigger
            || $this->extraActionsStorage->isScheduledForInsert($trigger)
            || $this->getRepository($em)->isExisting($trigger)
        ) {
            return;
        }

        $this->eventDispatcher->dispatch(ProductPriceChange::NAME, new ProductPriceChange());
        $this->extraActionsStorage->scheduleForExtraInsert($trigger);
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
     * @param EntityManagerInterface $em
     * @return ProductPriceChangeTriggerRepository
     */
    protected function getRepository(EntityManagerInterface $em)
    {
        if (!$this->repository) {
            $this->repository = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger');
        }

        return $this->repository;
    }

    /**
     * @param array $changeSet
     * @return bool
     */
    protected function isProductChanged(array $changeSet)
    {
        return array_key_exists('product', $changeSet) && $changeSet['product'][0] !== $changeSet['product'][1];
    }

    /**
     * @param array $changeSet
     * @return bool
     */
    protected function isPriceListChanged(array $changeSet)
    {
        return array_key_exists('priceList', $changeSet) && $changeSet['priceList'][0] !== $changeSet['priceList'][1];
    }

    /**
     * @param ProductPrice $productPrice
     * @param EntityManagerInterface $entityManager
     */
    protected function addPriceListToProductRelation(ProductPrice $productPrice, EntityManagerInterface $entityManager)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        $relation = $entityManager->getRepository('OroB2BPricingBundle:PriceListToProduct')
            ->findOneBy([
                'product' => $product,
                'priceList' => $priceList
            ]);

        if (null === $relation) {
            $relation = new PriceListToProduct();
            $relation->setPriceList($priceList)
                ->setProduct($product);
            if (!$this->isPriceListToProductScheduled($relation)) {
                $this->extraActionsStorage->scheduleForExtraInsert($relation);
            }
        }
    }

    /**
     * @param PriceListToProduct $priceListToProduct
     * @return bool
     */
    protected function isPriceListToProductScheduled(PriceListToProduct $priceListToProduct)
    {
        foreach ($this->extraActionsStorage->getScheduledForInsert() as $scheduled) {
            if ($scheduled instanceof PriceListToProduct
                && $scheduled->getProduct() === $priceListToProduct->getProduct()
                && $scheduled->getPriceList() === $priceListToProduct->getPriceList()
            ) {
                return true;
            }
        }

        return false;
    }
}
