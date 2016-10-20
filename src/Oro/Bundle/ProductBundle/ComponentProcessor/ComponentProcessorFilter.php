<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class ComponentProcessorFilter implements ComponentProcessorFilterInterface
{
    /** @var  ProductManager */
    protected $productManager;

    /** @var ProductRepository */
    protected $repository;

    /**
     * @param ProductManager    $productManager
     * @param ProductRepository $repository
     */
    public function __construct(
        ProductManager $productManager,
        ProductRepository $repository
    ) {
        $this->productManager = $productManager;
        $this->repository     = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function filterData(array $data, array $dataParameters)
    {
        $products = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $product) {
            $products[strtoupper($product[ProductDataStorage::PRODUCT_SKU_KEY])] = $product;
        }
        $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] = [];

        if (empty($products)) {
            return $data;
        }

        $searchQuery      = $this->repository->getFilterSkuQuery(array_keys($products));
        $this->productManager->restrictSearchQuery($searchQuery->getQuery());
        /** @var Item[] $filteredProducts */
        $filteredProducts = $searchQuery->getResult()->toArray();

        foreach ($filteredProducts as $product) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] =
                $products[strtoupper($product->getSelectedData()['sku'])];
        }

        return $data;
    }
}
