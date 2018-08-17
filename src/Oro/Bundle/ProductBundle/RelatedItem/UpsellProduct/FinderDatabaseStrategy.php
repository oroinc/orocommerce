<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;

class FinderDatabaseStrategy implements FinderStrategyInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var AbstractRelatedItemConfigProvider
     */
    private $configProvider;

    /**
     * @param DoctrineHelper                    $doctrineHelper
     * @param AbstractRelatedItemConfigProvider $configProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractRelatedItemConfigProvider $configProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Product $product, $bidirectional = false, $limit = null)
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getUpsellProductRepository()
            ->findUpsell(
                $product->getId(),
                $limit
            );
    }

    /**
     * @param Product   $product
     * @param bool      $bidirectional
     * @param int|null  $limit
     * @return Product[]
     */
    public function findIds(Product $product, $bidirectional = false, $limit = null)
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getUpsellProductRepository()
            ->findUpsellIds(
                $product->getId(),
                $limit
            );
    }

    /**
     * @return UpsellProductRepository|EntityRepository
     */
    private function getUpsellProductRepository()
    {
        return $this->doctrineHelper->getEntityRepository(UpsellProduct::class);
    }
}
