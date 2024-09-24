<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\PricingBundle\Provider\FormattedProductPriceProvider;

/**
 * Search for product with prices if results are accessible by visibility limited search.
 */
class ProductVisibilityLimitedWithPricesSearchHandler implements SearchHandlerInterface
{
    private const RESULTS = 'results';

    private SearchHandlerInterface $baseSearchHandler;
    private FormattedProductPriceProvider $formattedProductPriceProvider;

    public function __construct(
        SearchHandlerInterface $baseSearchHandler,
        FormattedProductPriceProvider $formattedProductPriceProvider
    ) {
        $this->baseSearchHandler = $baseSearchHandler;
        $this->formattedProductPriceProvider = $formattedProductPriceProvider;
    }

    #[\Override]
    public function search($query, $page, $perPage, $searchById = false)
    {
        $results = $this->baseSearchHandler->search($query, $page, $perPage, $searchById);
        if (\count($results[self::RESULTS])) {
            $ids = array_column($results[self::RESULTS], 'id');
            $prices = $this->formattedProductPriceProvider->getFormattedProductPrices($ids);
            $toRemove = [];
            foreach ($results[self::RESULTS] as $i => $item) {
                $id = $item['id'];
                if (isset($prices[$id])) {
                    $results[self::RESULTS][$i] = array_merge($item, $prices[$id]);
                } else {
                    $toRemove[] = $i;
                }
            }
            if ($toRemove) {
                foreach ($toRemove as $i) {
                    unset($results[self::RESULTS][$i]);
                }
                $results[self::RESULTS] = array_values($results[self::RESULTS]);
            }
        }

        return $results;
    }

    #[\Override]
    public function convertItem($item)
    {
        return $this->baseSearchHandler->convertItem($item);
    }

    #[\Override]
    public function getProperties()
    {
        return $this->baseSearchHandler->getProperties();
    }

    #[\Override]
    public function getEntityName()
    {
        return $this->baseSearchHandler->getEntityName();
    }
}
