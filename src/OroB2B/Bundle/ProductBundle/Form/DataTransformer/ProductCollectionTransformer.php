<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;

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
            if ($this->isFieldEmpty($product, ProductRowType::PRODUCT_SKU_FIELD_NAME) &&
                $this->isFieldEmpty($product, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME)
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
        return $data[$field] === null || $data[$field] === '';
    }
}
