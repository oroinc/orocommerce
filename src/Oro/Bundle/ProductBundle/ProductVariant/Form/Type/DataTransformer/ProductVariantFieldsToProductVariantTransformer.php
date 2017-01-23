<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Form\Type\DataTransformer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Symfony\Component\Form\DataTransformerInterface;

class ProductVariantFieldsToProductVariantTransformer implements DataTransformerInterface
{
    /** @var Product */
    private $parentProduct;

    /** @var ProductVariantAvailabilityProvider */
    private $productVariantAvailabilityProvider;

    /** @var string */
    private $productClass;

    /**
     * @param Product $parentProduct
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param string $productClass
     */
    public function __construct(
        Product $parentProduct,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        $productClass
    ) {
        $this->parentProduct = $parentProduct;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->productClass = (string) $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof $this->productClass) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value to transform of type %s expected, but %s given',
                    $this->productClass,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value instanceof $this->productClass) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value to reverse transform of type %s expected, but %s given',
                    $this->productClass,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $fields = $this->productVariantAvailabilityProvider
            ->getVariantFieldsValuesForVariant($this->parentProduct, $value);

        return $this->productVariantAvailabilityProvider
            ->getSimpleProductByVariantFields($this->parentProduct, $fields, false);
    }
}
