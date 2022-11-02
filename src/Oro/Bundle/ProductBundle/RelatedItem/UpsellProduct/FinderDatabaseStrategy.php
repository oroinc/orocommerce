<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;

/**
 * A service to get IDs of upsell products.
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
     */
    public function findIds(Product $product, bool $bidirectional = false, int $limit = null): array
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getUpsellProductRepository()
            ->findUpsellIds($product->getId(), $limit);
    }

    private function getUpsellProductRepository(): UpsellProductRepository
    {
        return $this->doctrine->getRepository(UpsellProduct::class);
    }
}
