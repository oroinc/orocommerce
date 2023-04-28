<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;

/**
 * Search for product with prices if results are accessible by visibility limited search.
 */
class ProductVisibilityLimitedWithPricesSearchHandler implements SearchHandlerInterface
{
    protected SearchHandlerInterface $productWithPricesSearchHandler;
    protected SearchHandlerInterface $productVisibilityLimitedSearchHandler;

    public function __construct(
        SearchHandlerInterface $productWithPricesSearchHandler,
        SearchHandlerInterface $productVisibilityLimitedSearchHandler
    ) {
        $this->productWithPricesSearchHandler = $productWithPricesSearchHandler;
        $this->productVisibilityLimitedSearchHandler = $productVisibilityLimitedSearchHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $results = $this->productVisibilityLimitedSearchHandler->search($query, $page, $perPage, $searchById);

        if (\count($results['results'])) {
            $ids = array_column($results['results'], 'id');
            $priceResults = $this->productWithPricesSearchHandler->search(implode(',', $ids), $page, $perPage, true);
            $priceResults['results'] = array_values(array_filter(
                $priceResults['results'],
                function ($result) use ($ids) {
                    return \in_array($result['id'], $ids);
                }
            ));

            return $priceResults;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        return $this->productWithPricesSearchHandler->convertItem($item);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->productWithPricesSearchHandler->getProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->productWithPricesSearchHandler->getEntityName();
    }
}
