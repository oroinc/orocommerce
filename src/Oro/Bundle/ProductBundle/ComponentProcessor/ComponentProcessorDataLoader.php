<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataLoaderInterface;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * Loads information about products for the service that maps a product identifier for each data item
 * that is received during submitting of Quick Add Form.
 */
class ComponentProcessorDataLoader implements ProductMapperDataLoaderInterface
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     *
     * @param string[] $skusUppercase
     *
     * @return array [['id' => product id, 'sku' => product sku, 'orgId' => product organization id], ...]
     */
    #[\Override]
    public function loadProducts(array $skusUppercase): array
    {
        $searchQuery = $this->productRepository->getFilterSkuQuery($skusUppercase);
        $searchQuery->addSelect('integer.organization_id');
        // Add marker `autocomplete_record_id` to be able to determine query context in listeners
        // `autocomplete_record_id` is used to be same to Quick Order Form behaviour
        $searchQuery->addSelect('integer.system_entity_id as autocomplete_record_id');
        $searchQuery->setOrderBy('integer.organization_id');

        $searchResult = $searchQuery->getResult();
        if (null === $searchResult) {
            throw new \RuntimeException('Result of search query cannot be null.');
        }

        $products = [];
        /** @var Item[] $filteredProducts */
        $filteredProducts = $searchResult->toArray();
        foreach ($filteredProducts as $item) {
            $itemData = $item->getSelectedData();
            $products[] = [
                'id'    => $itemData['autocomplete_record_id'],
                'sku'   => $itemData['sku'],
                'orgId' => $itemData['organization_id']
            ];
        }

        return $products;
    }
}
