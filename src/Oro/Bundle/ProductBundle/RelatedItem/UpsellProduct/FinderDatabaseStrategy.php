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
    public function __construct(
        private ManagerRegistry $doctrine,
        private RelatedItemConfigProviderInterface $configProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function findIds(Product $product): array
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        return $this->getUpsellProductRepository()->findUpsellIds(
            $product->getId(),
            $this->configProvider->getLimit()
        );
    }

    private function getUpsellProductRepository(): UpsellProductRepository
    {
        return $this->doctrine->getRepository(UpsellProduct::class);
    }
}
