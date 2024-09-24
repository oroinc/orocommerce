<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ProductVariantFieldsTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value)
    {
        return $value;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        uasort($value, function ($a, $b) {
            if (isset($a['priority']) && isset($b['priority'])) {
                return $a['priority'] - $b['priority'];
            }

            return 0;
        });

        $transformedData = [];
        foreach ($value as $name => $item) {
            if (isset($item['is_selected']) && $item['is_selected']) {
                $transformedData[] = $name;
            }
        }

        return $transformedData;
    }
}
