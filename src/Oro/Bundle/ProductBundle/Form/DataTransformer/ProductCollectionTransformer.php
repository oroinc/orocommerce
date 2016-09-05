<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

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
            if (is_null($product) || ($this->isFieldEmpty($product, ProductDataStorage::PRODUCT_SKU_KEY) &&
                    $this->isFieldEmpty($product, ProductDataStorage::PRODUCT_QUANTITY_KEY))
            ) {
                // clear unused field
                unset($value[$key]);
            }
        }

        return $value;
    }

    /**
     * @param ProductRow $data
     * @param string $field
     * @return bool
     */
    protected function isFieldEmpty(ProductRow $data, $field)
    {
        return $data->{$field} === null || $data->{$field} === '';
    }
}
