<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;

/**
 * Provides SKUs of the product kits that reference specified {@see ProductUnitPrecision}.
 */
class ProductKitsByUnitPrecisionProvider
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Returns an array of SKUs of the product kits that reference specified $productUnitPrecision.
     *
     * @param ProductUnitPrecision $productUnitPrecision
     * @param int $limit
     * @param string $ellipsis Adds $ellipsis after the last element of the returned array if
     *                            the number of found products kits is greater than $limit.
     *
     * @return string[]
     */
    public function getRelatedProductKitsSku(
        ProductUnitPrecision $productUnitPrecision,
        int $limit = 10,
        string $ellipsis = '...'
    ): array {
        if (!$productUnitPrecision->getId()) {
            return [];
        }

        /** @var ProductKitItemRepository $repository */
        $repository = $this->managerRegistry->getRepository(ProductKitItem::class);

        $skus = $repository->findProductKitsSkuByUnitPrecision($productUnitPrecision, $limit + 1);
        if ($ellipsis !== '' && count($skus) > $limit) {
            array_splice($skus, -1, 1, $ellipsis);
        }

        return $skus;
    }
}
