<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Remove product price attributes by unit on ProductUnitPrecision delete.
 */
class ProductUnitPrecisionPostRemoveListener
{
    /**
     * @var ShardManager
     */
    private $shardManager;

    /**
     * @param ShardManager $shardManager
     */
    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * @var string
     */
    protected $priceAttributeClass;

    /**
     * @param LifecycleEventArgs $event
     */
    public function postRemove(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof ProductUnitPrecision) {
            $product = $entity->getProduct();
            $unit = $entity->getUnit();

            if (!$product->getId()) {
                return;
            }

            /** @var PriceAttributeProductPriceRepository $repository */
            $repository = $event->getEntityManager()->getRepository($this->priceAttributeClass);
            $repository->deleteByProductUnit($this->shardManager, $product, $unit);
        }
    }

    /**
     * @param string $priceAttributeClass
     */
    public function setPriceAttributeClass($priceAttributeClass)
    {
        $this->priceAttributeClass = $priceAttributeClass;
    }
}
