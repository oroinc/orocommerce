<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use OroB2B\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

/**
 * Remove product prices by unit on ProductUnitPrecision delete.
 */
class ProductUnitPrecisionListener
{
    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param LifecycleEventArgs $event
     */
    public function postRemove(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof ProductUnitPrecision) {
            $args = [
                'unit' => $entity->getUnit(),
                'product' => $entity->getProduct()
            ];

            $this->eventDispatcher
                ->dispatch(ProductPricesRemoveBefore::NAME, new ProductPricesRemoveBefore($args));

            /** @var ProductPriceRepository $repository */
            $repository = $event->getEntityManager()
                ->getRepository($this->productPriceClass);
            $repository->deleteByProductUnit($entity->getProduct(), $entity->getUnit());

            $this->eventDispatcher
                ->dispatch(ProductPricesRemoveAfter::NAME, new ProductPricesRemoveAfter($args));
        }
    }

    /**
     * @param string $productPriceClass
     * @return ProductUnitPrecisionListener
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;

        return $this;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @return ProductUnitPrecisionListener
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
