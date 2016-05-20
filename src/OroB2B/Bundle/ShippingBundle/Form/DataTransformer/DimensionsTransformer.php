<?php

namespace OroB2B\Bundle\ShippingBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;

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
        if (!$dimensions instanceof Dimensions ||
            !$dimensions->getValue() instanceof DimensionsValue ||
            !$dimensions->getUnit() ||
            $dimensions->getValue()->isEmpty() ||
            $this->isDimensionsValueInvalid($dimensions->getValue())
        ) {
            return null;
        }

        return $dimensions;
    }

    protected function isDimensionsValueInvalid(DimensionsValue $value)
    {
        return $value->getLength() && !$this->isFloatValue($value->getLength()) ||
            $value->getWidth() && !$this->isFloatValue($value->getWidth()) ||
            $value->getHeight() && !$this->isFloatValue($value->getHeight());
    }

    /**
     * @param float|mixed $value
     * @return bool
     */
    protected function isFloatValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
}
