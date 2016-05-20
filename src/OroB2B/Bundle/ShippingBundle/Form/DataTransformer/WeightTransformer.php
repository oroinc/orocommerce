<?php

namespace OroB2B\Bundle\ShippingBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ShippingBundle\Model\Weight;

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
