<?php

namespace Oro\Bundle\CurrencyBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

class PriceTransformer implements DataTransformerInterface
{
    /**
     * @param Price|null $price
     * @return Price|null
     */
    public function transform($price)
    {
        return $price;
    }

    /**
     * @param Price|null $price
     * @return Price|null
     */
    public function reverseTransform($price)
    {
        if (!$price || !$price instanceof Price || !$price->getValue()) {
            return null;
        }

        return $price;
    }
}
