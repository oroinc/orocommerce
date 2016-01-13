<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreFlushEventArgs;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceEntityListener
{
    /**
     * @param ProductPrice $productPrice
     * @param PreFlushEventArgs $event
     */
    public function preFlush(ProductPrice $productPrice, PreFlushEventArgs $event)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();
        $changedProductPrice = new ChangedProductPrice($priceList, $product);

        $em = $event->getEntityManager();
        if ($this->changedProductPriceExists($em, $changedProductPrice)) {
            return;
        }

        $em->persist($changedProductPrice);

        $meta = $em->getClassMetadata(get_class($changedProductPrice));
        $uow = $em->getUnitOfWork();
        $uow->computeChangeSet($meta, $changedProductPrice);
    }

    /**
     * @param EntityManager $em
     * @param ChangedProductPrice $changedProductPrice
     * @return bool
     */
    protected function changedProductPriceExists(EntityManager $em, ChangedProductPrice $changedProductPrice)
    {
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity == $changedProductPrice) {
                return true;
            }
        };

        $repository = $em->getRepository('OroB2BPricingBundle:ChangedProductPrice');

        return (null !== $repository->findOneBy([
                'priceList' => $changedProductPrice->getPriceList(),
                'product' => $changedProductPrice->getProduct()
            ]));
    }
}
