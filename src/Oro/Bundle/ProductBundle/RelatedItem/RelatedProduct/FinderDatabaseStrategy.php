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

    /**
     * {@inheritDoc}
     * If parameters `bidirectional` and `limit` are not passed - default values from configuration will be used
     */
    public function findIds(Product $product, bool $bidirectional = false, int $limit = null): array
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getRelatedProductsRepository()
            ->findRelatedIds($product->getId(), $bidirectional, $limit);
    }

    private function getRelatedProductsRepository(): RelatedProductRepository
    {
        return $this->doctrine->getRepository(RelatedProduct::class);
    }
}
