<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataAccessorInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * The service to access data required to map a product for each row in QuickAddRowCollection.
 */
class QuickAddRowDataAccessor implements ProductMapperDataAccessorInterface
{
    /**
     *
     * @param QuickAddRowCollection $collection
     * @param int                   $itemIndex
     *
     * @return QuickAddRow
     */
    #[\Override]
    public function getItem(object $collection, int $itemIndex): object
    {
        return $collection[$itemIndex];
    }

    /**
     *
     * @param QuickAddRow $item
     *
     * @return string|null
     */
    #[\Override]
    public function getItemSku(object $item): ?string
    {
        return $item->getSku();
    }

    /**
     *
     * @param QuickAddRow $item
     *
     * @return string|null
     */
    #[\Override]
    public function getItemOrganizationName(object $item): ?string
    {
        return $item->getOrganization();
    }

    /**
     *
     * @param Product $product
     *
     * @return string|null
     */
    #[\Override]
    public function getProductSku(mixed $product): ?string
    {
        return $product->getSku();
    }

    /**
     *
     * @param Product $product
     *
     * @return int|null
     */
    #[\Override]
    public function getProductOrganizationId(mixed $product): ?int
    {
        return $product->getOrganization()?->getId();
    }

    /**
     *
     * @param QuickAddRow $item
     * @param Product     $product
     */
    #[\Override]
    public function updateItem(object $item, mixed $product): void
    {
        if (null === $item->getProduct()) {
            $item->setProduct($product);
            if (null === $item->getUnit()) {
                $item->setUnit($product->getPrimaryUnitPrecision()->getUnit()->getCode());
            }
        }
    }
}
