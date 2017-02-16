<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_variant_links';

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
     * @param ProductVariantLinks|Constraint $constraint
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

        if (!$value->isConfigurable()) {
            return;
        }

        $this->validateLinksWithoutFields($value, $constraint);
        $this->validateLinksHaveFilledFields($value, $constraint);
    }

    /**
     * Add violation if variant fields are empty but variant links are present
     *
     * @param Product $value
     * @param ProductVariantLinks $constraint
     */
    private function validateLinksWithoutFields(Product $value, ProductVariantLinks $constraint)
    {
        if (count($value->getVariantFields()) === 0 && $value->getVariantLinks()->count() !== 0) {
            $this->context->addViolationAt('variantFields', $constraint->variantFieldRequiredMessage);
        }
    }

    /**
     * @param Product $value
     * @param ProductVariantLinks $constraint
     */
    private function validateLinksHaveFilledFields(Product $value, ProductVariantLinks $constraint)
    {
        $variantFields = $value->getVariantFields();
        $variantLinks = $value->getVariantLinks();

        $errorsData = [];
        foreach ($variantLinks as $variantLink) {
            $product = $variantLink->getProduct();
            if (!$product) {
                continue;
            }

            foreach ($variantFields as $variantField) {
                if ($this->propertyAccessor->isReadable($product, $variantField)) {
                    $variantFieldValue = $this->propertyAccessor->getValue($product, $variantField);

                    if ($variantFieldValue === null) {
                        $errorsData[$product->getSku()][] = $variantField;
                    }
                }
            }
        }

        foreach ($errorsData as $productSku => $variantFields) {
            $this->context->addViolation(
                $constraint->variantLinkHasNoFilledFieldMessage,
                [
                    '%product_sku%' => $productSku,
                    '%fields%' => implode(', ', $variantFields)
                ]
            );
        }
    }
}
