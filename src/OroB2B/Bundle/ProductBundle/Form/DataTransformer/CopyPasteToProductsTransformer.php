<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class CopyPasteToProductsTransformer implements DataTransformerInterface
{
    private static $skuPosition = 1;
    private static $quantityPosition = 0;
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
        $result = [];

        $lines = explode("\n", $value);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        foreach ($lines as $line) {
            $line = preg_replace('/\t+/', ',', $line);
            $lineParts = explode(',', $line);
            $lineParts = array_map('trim', $lineParts);
            $lineParts = array_filter($lineParts);

            $result[] = [
                ProductDataStorage::PRODUCT_QUANTITY_KEY => $lineParts[self::$quantityPosition],
                ProductDataStorage::PRODUCT_SKU_KEY => $lineParts[self::$skuPosition]
            ];
        }

        return $result;
    }
}
