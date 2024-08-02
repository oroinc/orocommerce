<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;

/**
 * A service to get IDs of related products.
 */
class FinderDatabaseStrategy implements FinderStrategyInterface
{
    private ManagerRegistry $doctrine;
    private RelatedItemConfigProviderInterface $configProvider;

    public function __construct(ManagerRegistry $doctrine, RelatedItemConfigProviderInterface $configProvider)
    {
        $this->doctrine = $doctrine;
        $this->configProvider = $configProvider;
    }

    public function findIds(Product $product, bool $bidirectional = false, int $limit = null): array
    {
        return $this->doFind($product, $this->configProvider->isBidirectional());
    }

    public function findNotBidirectionalIds(Product $product): array
    {
        return $this->doFind($product, false);
    }

    private function getRelatedProductsRepository(): RelatedProductRepository
    {
        return $this->doctrine->getRepository(RelatedProduct::class);
    }

    private function doFind(Product $product, bool $isBidirectional): array
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getRelatedProductsRepository()
            ->findRelatedIds($product->getId(), $isBidirectional, $this->configProvider->getLimit());
    }
}
