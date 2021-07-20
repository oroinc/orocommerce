<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms an NULL to empty array and ArrayCollection to array.
 */
class ProductImageTypesTransformer implements DataTransformerInterface
{
    /**
     * @param mixed $value
     * @return array
     */
    public function transform($value): array
    {
        if ($value instanceof ArrayCollection) {
            return $value->toArray();
        }

        return [];
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function reverseTransform($value): array
    {
        // Data should not be transformed
        return $value;
    }
}
