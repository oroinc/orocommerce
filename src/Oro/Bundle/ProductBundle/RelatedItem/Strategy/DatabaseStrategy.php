<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\Strategy;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\ConfigProvider\AbstractRelatedItemConfigProvider;

class DatabaseStrategy implements StrategyInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var AbstractRelatedItemConfigProvider
     */
    private $configProvider;

    public function __construct(DoctrineHelper $doctrineHelper, AbstractRelatedItemConfigProvider $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function findRelatedProducts(Product $product, array $context = [])
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getProductRepository()
            ->findRelated(
                $product->getId(),
                $this->configProvider->isBidirectional(),
                $this->configProvider->getLimit()
            );
    }

    /**
     * @return ProductRepository
     */
    private function getProductRepository()
    {
        return $this->doctrineHelper
            ->getEntityManager(Product::class)
            ->getRepository(Product::class);
    }
}
