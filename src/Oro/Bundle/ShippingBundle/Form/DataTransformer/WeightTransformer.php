<?php

namespace Oro\Bundle\ShippingBundle\Form\DataTransformer;

use Oro\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Validates and transforms weight model data.
 */
class WeightTransformer implements DataTransformerInterface
{
    /**
     * @param Weight|null $weight
     * @return Weight|null
     */
    #[\Override]
    public function transform($weight): mixed
    {
        return $weight;
    }

    /**
     * @param Weight|null $weight
     * @return Weight|null
     */
    #[\Override]
    public function reverseTransform($weight): mixed
    {
        if (
            !$weight instanceof Weight ||
            !$weight->getUnit() ||
            !$weight->getValue() ||
            filter_var($weight->getValue(), FILTER_VALIDATE_FLOAT) === false
        ) {
            return null;
        }

        return $weight;
    }
}
