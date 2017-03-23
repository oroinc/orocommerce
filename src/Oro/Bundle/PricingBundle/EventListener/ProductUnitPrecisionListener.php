<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @param LifecycleEventArgs $event
     */
    public function postRemove(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof ProductUnitPrecision) {
            $product = $entity->getProduct();
            $unit = $entity->getUnit();
            // prices are already removed using cascade delete operation
            if (!$product->getId()) {
                return;
            }
            //TODO: check reindex for prices
            $args = ['unit' => $unit, 'product' => $product];
            $this->eventDispatcher
                ->dispatch(ProductPricesRemoveBefore::NAME, new ProductPricesRemoveBefore($args));
            
            /** @var ProductPriceRepository $repository */
            $repository = $event->getEntityManager()->getRepository($this->productPriceClass);
            $repository->deleteByProductUnit($this->shardManager, $product, $unit);
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

    /**
     * @param ShardManager $shardManager
     */
    public function setShardManager(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }
}
