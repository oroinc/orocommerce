<?php

namespace Oro\Bundle\ShippingBundle\Form\DataTransformer;

use Oro\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\Form\DataTransformerInterface;

class WeightTransformer implements DataTransformerInterface
{
    /**
     * @param Weight|null $weight
     * @return Weight|null
     */
    public function transform($weight)
    {
        return $weight;
    }

    /**
     * @param Weight|null $weight
     * @return Weight|null
     */
    public function reverseTransform($weight)
    {
        if (!$weight instanceof Weight ||
            !$weight->getUnit() ||
            !$weight->getValue() ||
            filter_var($weight->getValue(), FILTER_VALIDATE_FLOAT) === false
        ) {
            return null;
        }

        return $weight;
    }
}
