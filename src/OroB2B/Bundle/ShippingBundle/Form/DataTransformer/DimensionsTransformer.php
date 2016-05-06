<?php

namespace OroB2B\Bundle\ShippingBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ShippingBundle\Model\Dimensions;

class DimensionsTransformer implements DataTransformerInterface
{
    /**
     * @param Dimensions|null $dimensions
     * @return Dimensions|null
     */
    public function transform($dimensions)
    {
        return $dimensions;
    }

    /**
     * @param Dimensions|null $dimensions
     * @return Dimensions|null
     */
    public function reverseTransform($dimensions)
    {
        if (!$dimensions ||
            !$dimensions instanceof Dimensions ||
            filter_var($dimensions->getLength(), FILTER_VALIDATE_FLOAT) === false ||
            filter_var($dimensions->getWidth(), FILTER_VALIDATE_FLOAT) === false ||
            filter_var($dimensions->getHeight(), FILTER_VALIDATE_FLOAT) === false
        ) {
            return null;
        }

        return $dimensions;
    }
}
