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

    /**
     * @param SearchHandlerInterface $productWithPricesSearchHandler
     * @param SearchHandlerInterface $productVisibilityLimitedSearchHandler
     */
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

        if (count($results['results'])) {
            $priceResults = $this->productWithPricesSearchHandler->search($query, $page, $perPage, $searchById);
            $results['results'] = $this->getResultsBasedOnPriceResults($results['results'], $priceResults['results']);
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

    /**
     * @param array $results
     * @param array $priceResults
     *
     * @return array
     */
    private function getResultsBasedOnPriceResults(array $results, array $priceResults)
    {
        $priceResultsById = [];
        foreach ($priceResults as $data) {
            $priceResultsById[$data['id']] = $data;
        }

        foreach ($results as $key => $result) {
            if (array_key_exists($result['id'], $priceResultsById)) {
                $results[$key] = array_merge($result, $priceResultsById[$result['id']]);
            } else {
                unset($results[$key]);
            }
        }

        return array_values($results);
    }
}
