<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataAccessorInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

/**
 * The service to access data required to map a product identifier for each data item
 * that is received during submitting of Quick Add Form.
 */
class ComponentProcessorDataAccessor implements ProductMapperDataAccessorInterface
{
    /**
     *
     * @param Collection $collection
     * @param int        $itemIndex
     *
     * @return \ArrayAccess
     */
    #[\Override]
    public function getItem(object $collection, int $itemIndex): object
    {
        return $collection[$itemIndex];
    }

    /**
     *
     * @param \ArrayAccess $item
     *
     * @return string|null
     */
    #[\Override]
    public function getItemSku(object $item): ?string
    {
        return $item[ProductDataStorage::PRODUCT_SKU_KEY];
    }

    /**
     *
     * @param \ArrayAccess $item
     *
     * @return string|null
     */
    #[\Override]
    public function getItemOrganizationName(object $item): ?string
    {
        return $item[ProductDataStorage::PRODUCT_ORGANIZATION_KEY] ?? null;
    }

    /**
     *
     * @param array $product
     *
     * @return string|null
     */
    #[\Override]
    public function getProductSku(mixed $product): ?string
    {
        if (!\array_key_exists('sku', $product)) {
            throw new \RuntimeException('The "sku" attribute does not exist.');
        }

        return $product['sku'];
    }

    /**
     *
     * @param array $product
     *
     * @return int|null
     */
    #[\Override]
    public function getProductOrganizationId(mixed $product): ?int
    {
        if (!\array_key_exists('orgId', $product)) {
            throw new \RuntimeException('The "orgId" attribute does not exist.');
        }

        return $product['orgId'];
    }

    /**
     *
     * @param \ArrayAccess $item
     * @param array        $product
     */
    #[\Override]
    public function updateItem(object $item, mixed $product): void
    {
        if (!isset($item[ProductDataStorage::PRODUCT_ID_KEY])) {
            if (!\array_key_exists('id', $product)) {
                throw new \RuntimeException('The "id" attribute does not exist.');
            }
            $item[ProductDataStorage::PRODUCT_ID_KEY] = $product['id'];
        }
    }
}
