<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Remove product prices by unit on ProductUnitPrecision delete.
 */
class ProductUnitPrecisionListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

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
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(
        $productPriceClass,
        EventDispatcherInterface $dispatcher,
        ShardManager $shardManager,
        DoctrineHelper $helper
    ) {
        $this->productPriceClass = $productPriceClass;
        $this->eventDispatcher = $dispatcher;
        $this->shardManager = $shardManager;
        $this->doctrineHelper = $helper;
    }

    public function postRemove(ProductUnitPrecision $precision, LifecycleEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $product = $precision->getProduct();
        $unit = $precision->getUnit();
        // prices are already removed using cascade delete operation
        if (!$product->getId()) {
            return;
        }
        $args = ['unit' => $product, 'product' => $unit];
        $this->eventDispatcher
            ->dispatch(new ProductPricesRemoveBefore($args), ProductPricesRemoveBefore::NAME);

        /** @var ProductPriceRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->productPriceClass);
        $repository->deleteByProductUnit($this->shardManager, $product, $unit);
        $this->eventDispatcher
            ->dispatch(new ProductPricesRemoveAfter($args), ProductPricesRemoveAfter::NAME);
    }
}
