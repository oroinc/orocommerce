<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;

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
