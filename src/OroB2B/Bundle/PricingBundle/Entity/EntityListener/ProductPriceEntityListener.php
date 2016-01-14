<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceEntityListener
{
    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function prePersist(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->persistChangedProductPrice($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function preRemove(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->persistChangedProductPrice($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    protected function persistChangedProductPrice(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $changedProductPrice = $this->createChangedProductPrice($productPrice);

        $em = $event->getEntityManager();
        if ($this->isChangedProductPriceCreated($em, $changedProductPrice)) {
            return;
        }

        $em->persist($changedProductPrice);
    }

    /**
     * @param ProductPrice $productPrice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $event)
    {
        // todo does not work yet

        $em = $event->getEntityManager();
        $changedProductPrice = $this->createChangedProductPrice($productPrice);

        if ($this->isChangedProductPriceCreated($em, $changedProductPrice)) {
            return;
        }

        $em->persist($changedProductPrice);
    }

    /**
     * @param ProductPrice $productPrice
     * @return ChangedProductPrice
     */
    protected function createChangedProductPrice(ProductPrice $productPrice)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();
        return new ChangedProductPrice($priceList, $product);
    }

    /**
     * @param EntityManager $em
     * @param ChangedProductPrice $changedProductPrice
     * @return bool
     */
    protected function isChangedProductPriceCreated(EntityManager $em, ChangedProductPrice $changedProductPrice)
    {
        //check if same entity already has been scheduled for insert
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity == $changedProductPrice) {
                return true;
            }
        };

        //check if entity exists in db
        $repository = $em->getRepository('OroB2BPricingBundle:ChangedProductPrice');

        //todo repository
        return (bool)$repository->findOneBy([
                'priceList' => $changedProductPrice->getPriceList(),
                'product' => $changedProductPrice->getProduct()
        ]);
    }
}
