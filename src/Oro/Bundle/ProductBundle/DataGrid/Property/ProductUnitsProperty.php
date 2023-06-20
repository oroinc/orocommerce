<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\DataGrid\Property;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides an array of unit codes with precision.
 */
class ProductUnitsProperty
{
    /**
     * @param Product $product
     *
     * @return array<string,array{precision: int}>
     *  [
     *      'item' => [
     *          'precision' => 2,
     *      ],
     *      // ...
     *  ]
     */
    public function getProductUnits(Product $product): array
    {
        $list = [];
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            if (!$unitPrecision->isSell()) {
                continue;
            }

            $list[$unitPrecision->getUnit()->getCode()] = [
                'precision' => $unitPrecision->getPrecision(),
            ];
        }

        return $list;
    }
}
