<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * Filters given products, removes those which cannot be found through search.
 */
class ComponentProcessorFilter implements ComponentProcessorFilterInterface
{
    /** @var ProductRepository */
    protected $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function filterData(array $data, array $dataParameters)
    {
        $products = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $product) {
            $upperSku = mb_strtoupper($product[ProductDataStorage::PRODUCT_SKU_KEY]);

            if (!isset($products[$upperSku])) {
                $products[$upperSku] = [];
            }

            $products[$upperSku][] = $product;
        }

        $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] = [];

        if (empty($products)) {
            return $data;
        }

        $searchQuery = $this->repository->getFilterSkuQuery(array_keys($products));
        // Add marker `autocomplete_record_id` to be able to determine query context in listeners
        // `autocomplete_record_id` is used to be same to Quick Order Form behaviour
        $searchQuery->addSelect('integer.system_entity_id as autocomplete_record_id');
        /** @var Item[] $filteredProducts */
        $filteredProducts = $searchQuery->getResult();

        if ($filteredProducts === null) {
            throw new \RuntimeException("Result of search query cannot be null.");
        }

        $filteredProducts = $filteredProducts->toArray();

        foreach ($filteredProducts as $productEntry) {
            $product = $productEntry->getSelectedData();
            if (isset($product['sku'])) {
                $upperSku = mb_strtoupper($productEntry->getSelectedData()['sku']);
                foreach ($products[$upperSku] as $product) {
                    $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = $product;
                }
            }
        }

        return $data;
    }
}
