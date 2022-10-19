<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Interface for the providers that fetch data from {@see QuickAddRowCollection} for using it on storefront.
 */
interface QuickAddImportResultsProviderInterface
{
    /**
     * @param QuickAddRowCollection $quickAddRowCollection
     * @return array<string,array>
     *     [
     *          'sku1' => [
     *              'key' => 'value',
     *              // ...
     *          ],
     *          // ...
     *     ]
     */
    public function getResults(QuickAddRowCollection $quickAddRowCollection): array;
}
