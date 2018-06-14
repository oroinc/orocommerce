<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

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

    /**
     * @param DoctrineHelper $doctrineHelper
     */
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

    /**
     * @param PriceManager $priceManager
     */
    public function setPriceManager(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * Copy product prices
     *
     * @param ProductDuplicateAfterEvent $event
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

    /**
     * @param ShardManager $shardManager
     */
    public function setShardManager(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }
}
