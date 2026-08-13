<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * Doctrine repository for ProductTaxCode entity.
 *
 * Provides data access methods for product tax codes used in tax calculations.
 * Product tax codes are assigned to products to determine which tax rules apply
 * when calculating taxes for line items in orders.
 *
 * @see \Oro\Bundle\TaxBundle\Entity\ProductTaxCode
 */
class ProductTaxCodeRepository extends AbstractTaxCodeRepository
{
    #[\Override]
    public function findManyByEntities(array $objects): array
    {
        if (!$objects) {
            return [];
        }

        $productIds = [];
        foreach ($objects as $object) {
            assert($object instanceof Product);

            $productIds[] = $object->getId();
        }

        $queryBuilder = $this->createQueryBuilder('taxCode');
        $queryBuilder
            ->addSelect('JSON_AGG(product.id) as productIds')
            ->innerJoin(Product::class, 'product', 'WITH', 'product.taxCode = taxCode')
            ->groupBy('taxCode.id')
            ->where($queryBuilder->expr()->in('product.id', ':productIds'))
            ->setParameter('productIds', $productIds, ArrayParameterType::INTEGER);

        /** @var array<array{0: ProductTaxCode, productIds: string}> $taxCodesByProductId */
        $taxCodesData = $queryBuilder->getQuery()->getResult();
        /** @var array<int,ProductTaxCode> $taxCodes */
        $taxCodesByProductId = [];
        foreach ($taxCodesData as $key => $taxCodeData) {
            $productIds = json_decode($taxCodeData['productIds'], true, 2, JSON_THROW_ON_ERROR);
            foreach ($productIds as $productId) {
                $taxCodesByProductId[$productId] = $taxCodeData[0];
            }
        }

        /** @var array<ProductTaxCode|null> $result */
        $result = [];
        /** @var Product $object */
        foreach ($objects as $object) {
            $result[] = $taxCodesByProductId[$object->getId()] ?? null;
        }

        return $result;
    }
}
