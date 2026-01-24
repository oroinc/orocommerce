<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Converts shipping price objects to array representation.
 *
 * Transforms {@see Price} objects within shipping method and type data structures into plain arrays
 * containing value and currency information, suitable for serialization and API responses.
 */
class ShippingPricesConverter
{
    /**
     * @param array $data
     * @return array
     */
    public function convertPricesToArray(array $data)
    {
        return array_map(function ($methodData) {
            $methodData['types'] = array_map(function ($typeData) {
                /** @var Price $price */
                $price = $typeData['price'];
                $typeData['price'] = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency(),
                ];
                return $typeData;
            }, $methodData['types']);
            return $methodData;
        }, $data);
    }
}
