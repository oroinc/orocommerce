<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ProductBundle\Entity\Product;

class EmptyVariantFieldInSimpleProductForVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_empty_variant_field_in_simple_product_for_variant_links';

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param Product $value
     * @param UniqueVariantLinksSimpleProduct|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_a($value, Product::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    Product::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if ($value->isConfigurable() || $value->getParentVariantLinks()->count() === 0) {
            return;
        }

        $this->validateEmptyVariantField($value, $constraint);
    }

    /**
     * @param Product $product
     * @param UniqueVariantLinksSimpleProduct|Constraint $constraint
     */
    private function validateEmptyVariantField(Product $product, Constraint $constraint)
    {
        $errorsData = [];
        foreach ($product->getParentVariantLinks() as $parentVariantLink) {
            $parentProduct = $parentVariantLink->getParentProduct();
            $variantFields = $parentProduct->getVariantFields();

            foreach ($variantFields as $variantField) {
                if ($this->propertyAccessor->isReadable($product, $variantField)) {
                    $productValue = $this->propertyAccessor->getValue($product, $variantField);

                    if ($productValue === null) {
                        $errorsData[$variantField][] = $parentProduct->getSku();
                    }
                }
            }
        }

        if ($errorsData) {
            foreach ($errorsData as $variantField => $parentProductsSku) {
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '%variantField%' => $variantField,
                        '%products%' => implode(', ', $parentProductsSku)
                    ]
                );
            }
        }
    }
}
