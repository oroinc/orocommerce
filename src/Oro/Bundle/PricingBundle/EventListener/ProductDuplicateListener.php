<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

/**
 * Creates copies of product prices
 */
class ProductDuplicateListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var PriceManager
     */
    protected $priceManager;

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $productPriceClass
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;
    }

    public function setPriceManager(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * Copy product prices
     */
    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();

        $productPrices = $this->getProductPriceRepository()->getPricesByProduct($this->shardManager, $sourceProduct);

        foreach ($productPrices as $productPrice) {
            $productPriceCopy = clone $productPrice;
            $productPriceCopy->setProduct($product);
            $this->priceManager->persist($productPriceCopy);
        }

        $this->priceManager->flush();
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productPriceClass);
    }

    public function setShardManager(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }
}
