<?php

namespace Oro\Bundle\ShippingBundle\Form\DataTransformer;

use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Validates and transforms dimensions model data.
 */
class DimensionsTransformer implements DataTransformerInterface
{
    /**
     * @param Dimensions|null $dimensions
     * @return Dimensions|null
     */
    #[\Override]
    public function transform($dimensions): mixed
    {
        return $dimensions;
    }

    /**
     * @param Dimensions|null $dimensions
     * @return Dimensions|null
     */
    #[\Override]
    public function reverseTransform($dimensions): mixed
    {
        if (
            !$dimensions instanceof Dimensions ||
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
