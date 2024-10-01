<?php

namespace Oro\Bundle\ProductBundle\Model\Mapping;

/**
 * The service to map a product for each item in an item collection.
 */
class ProductMapper implements ProductMapperInterface
{
    private ProductMapperDataAccessorInterface $dataAccessor;
    private ProductMapperDataLoaderInterface $dataLoader;

    public function __construct(
        ProductMapperDataAccessorInterface $dataAccessor,
        ProductMapperDataLoaderInterface $dataLoader
    ) {
        $this->dataAccessor = $dataAccessor;
        $this->dataLoader = $dataLoader;
    }

    #[\Override]
    public function mapProducts(object $collection): void
    {
        $skusUppercase = [];
        $itemProductMap = [];
        foreach ($collection as $itemIndex => $item) {
            $sku = $this->dataAccessor->getItemSku($item);
            if (!$sku) {
                continue;
            }

            $skuUppercase = mb_strtoupper($sku);
            $skusUppercase[] = $skuUppercase;
            $itemProductMap[$skuUppercase][] = $itemIndex;
        }
        if (!$skusUppercase) {
            return;
        }

        $skusUppercase = array_values(array_unique($skusUppercase));

        $products = $this->dataLoader->loadProducts($skusUppercase);
        foreach ($products as $product) {
            $itemIndexes = $itemProductMap[mb_strtoupper($this->dataAccessor->getProductSku($product))] ?? null;
            if (null === $itemIndexes) {
                continue;
            }

            foreach ($itemIndexes as $itemIndex) {
                $this->dataAccessor->updateItem($this->dataAccessor->getItem($collection, $itemIndex), $product);
            }
        }
    }
}
