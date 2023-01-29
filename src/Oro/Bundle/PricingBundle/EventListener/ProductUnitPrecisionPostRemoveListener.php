<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Removes product price attributes by unit for deleted product unit precision.
 */
class ProductUnitPrecisionPostRemoveListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private ShardManager $shardManager;

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    public function postRemove(ProductUnitPrecision $productUnitPrecision, LifecycleEventArgs $args): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $product = $productUnitPrecision->getProduct();
        if (!$product->getId()) {
            return;
        }

        /** @var PriceAttributeProductPriceRepository $repository */
        $repository = $args->getObjectManager()->getRepository(PriceAttributeProductPrice::class);
        $repository->deleteByProductUnit($this->shardManager, $product, $productUnitPrecision->getUnit());
    }
}
