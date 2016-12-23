<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ProductVariantFieldsTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        usort($value, function ($a, $b) {
            if (isset($a['priority']) && isset($b['priority'])) {
                return $a['priority'] - $b['priority'];
            }

            return 0;
        });

        $transformedData = [];
        foreach ($value as $item) {
            if (isset($item['is_default']) && isset($item['id']) && $item['is_default']) {
                $transformedData[] = $item['id'];
            }
        }

        return $transformedData;
    }
}
