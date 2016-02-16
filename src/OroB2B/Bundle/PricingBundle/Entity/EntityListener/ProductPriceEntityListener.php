<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Event\ProductPriceChange;

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
            || $this->getRepository($em)->isCreated($trigger)
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
     * @param EntityManager $em
     * @return ProductPriceChangeTriggerRepository
     */
    protected function getRepository(EntityManager $em)
    {
        if (!$this->repository) {
            $this->repository = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger');
        }

        return $this->repository;
    }
}
