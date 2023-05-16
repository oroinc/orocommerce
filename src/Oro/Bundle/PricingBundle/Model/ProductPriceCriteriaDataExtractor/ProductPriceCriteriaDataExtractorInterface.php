<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;

/**
 * Interface for the services extracting data from product price criteria models for passing to product price storage.
 */
interface ProductPriceCriteriaDataExtractorInterface
{
    public const PRODUCT_IDS = 'productIds';
    public const UNIT_CODES = 'unitCodes';
    public const CURRENCIES = 'currencies';

    /**
     * @param ProductPriceCriteria $productPriceCriteria
     *
     * @return array{productIds: int, unitCodes: string, currencies: string} Criteria data needed
     *  for getting product prices from {@see ProductPriceStorageInterface}. Example:
     *      [
     *          'productIds' => [42, 142, ...],
     *          'unitCodes' => ['item', 'set', ...],
     *          'currencies' => ['USD', 'EUR', ...],
     *          // ...
     *      ]
     */
    public function extractCriteriaData(ProductPriceCriteria $productPriceCriteria): array;

    public function isSupported(ProductPriceCriteria $productPriceCriteria): bool;
}
