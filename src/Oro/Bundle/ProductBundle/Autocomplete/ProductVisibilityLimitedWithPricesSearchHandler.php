<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;

/**
 * Search for product with prices if results are accessible by visibility limited search.
 */
class ProductVisibilityLimitedWithPricesSearchHandler implements SearchHandlerInterface
{
    /**
     * @var SearchHandlerInterface
     */
    protected $productWithPricesSearchHandler;

    /**
     * @var SearchHandlerInterface
     */
    protected $productVisibilityLimitedSearchHandler;

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
            $skus = array_column($results['results'], 'sku');
            $skus = array_map('mb_strtoupper', $skus);
            $priceResults = $this->productWithPricesSearchHandler->search($query, $page, $perPage, $searchById);
            $priceResults['results'] = array_values(array_filter(
                $priceResults['results'],
                function ($result) use ($skus) {
                    return \in_array(mb_strtoupper($result['sku']), $skus, true);
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
