<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\QuickAdd\Normalizer;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Interface for the classes that normalize {@see QuickAddRowCollection} for using it on storefront.
 */
interface QuickAddCollectionNormalizerInterface
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
    public function normalize(QuickAddRowCollection $quickAddRowCollection): array;
}
