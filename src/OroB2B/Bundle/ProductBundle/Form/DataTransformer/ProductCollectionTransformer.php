<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductCollectionTransformer implements DataTransformerInterface
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
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $product) {
            if ($this->isFieldEmpty($product, ProductDataStorage::PRODUCT_SKU_KEY) &&
                $this->isFieldEmpty($product, ProductDataStorage::PRODUCT_QUANTITY_KEY)
            ) {
                // clear unused field
                unset($value[$key]);
            }
        }

        return $value;
    }

    /**
     * @param array $data
     * @param string $field
     * @return bool
     */
    protected function isFieldEmpty(array $data, $field)
    {
        return !array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '';
    }
}
