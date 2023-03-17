<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides the minimal product unit precision from all unit precisions referenced by the specified product kit item.
 */
class ProductKitItemUnitPrecisionProvider
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getUnitPrecisionByKitItem(ProductKitItem $productKitItem): int
    {
        if ($productKitItem->getId() === null) {
            return 0;
        }

        $unitPrecisionRepo = $this->managerRegistry->getRepository(ProductUnitPrecision::class);
        $queryBuilder = $unitPrecisionRepo->createQueryBuilder('pup');

        return (int) $queryBuilder
            ->select($queryBuilder->expr()->min('pup.precision'))
            ->innerJoin(
                ProductKitItemProduct::class,
                'pkip',
                Join::WITH,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('pkip.kitItem', ':kitItemId'),
                    $queryBuilder->expr()->eq('pkip.productUnitPrecision', 'pup')
                )
            )
            ->setParameter('kitItemId', $productKitItem->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
