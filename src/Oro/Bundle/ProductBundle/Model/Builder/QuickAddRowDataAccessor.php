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
     * {@inheritDoc}
     *
     * @param QuickAddRowCollection $collection
     * @param int                   $itemIndex
     *
     * @return QuickAddRow
     */
    public function getItem(object $collection, int $itemIndex): object
    {
        return $collection[$itemIndex];
    }

    /**
     * {@inheritDoc}
     *
     * @param QuickAddRow $item
     *
     * @return string|null
     */
    public function getItemSku(object $item): ?string
    {
        return $item->getSku();
    }

    /**
     * {@inheritDoc}
     *
     * @param QuickAddRow $item
     *
     * @return string|null
     */
    public function getItemOrganizationName(object $item): ?string
    {
        return $item->getOrganization();
    }

    /**
     * {@inheritDoc}
     *
     * @param Product $product
     *
     * @return string|null
     */
    public function getProductSku(mixed $product): ?string
    {
        return $product->getSku();
    }

    /**
     * {@inheritDoc}
     *
     * @param Product $product
     *
     * @return int|null
     */
    public function getProductOrganizationId(mixed $product): ?int
    {
        return $product->getOrganization()?->getId();
    }

    /**
     * {@inheritDoc}
     *
     * @param QuickAddRow $item
     * @param Product     $product
     */
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
