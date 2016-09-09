<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\ProductBundle\Entity\Product;

class UniqueProductVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_unique_variant_links';

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Product) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (!$value->getHasVariants()) {
            return;
        }

        $this->validateLinksWithoutFields($value, $constraint);
        $this->validateVariantLinks($value, $constraint);
    }

    /**
     * Add violation if variant fields empty but variant links presented
     *
     * @param Product $value
     * @param UniqueProductVariantLinks $constraint
     */
    private function validateLinksWithoutFields(Product $value, UniqueProductVariantLinks $constraint)
    {
        if (count($value->getVariantFields()) === 0 && $value->getVariantLinks()->count() !== 0) {
            $this->context->addViolationAt('variantFields', $constraint->variantFieldRequiredMessage);
        }
    }

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks $constraint
     */
    private function validateVariantLinks(Product $value, UniqueProductVariantLinks $constraint)
    {
        $variantHashes = [];
        $variantFields = $value->getVariantFields();
        foreach ($value->getVariantLinks() as $variantLink) {
            $product = $variantLink->getProduct();
            if (!$product) {
                continue;
            }

            $variantHashes[] = $this->getVariantFieldsHash($variantFields, $product);
        }

        if (count($variantHashes) !== count(array_unique($variantHashes))) {
            $this->context->addViolation($constraint->uniqueRequiredMessage);
        }
    }

    /**
     * @param array $variantFields
     * @param Product $product
     * @return string
     */
    private function getVariantFieldsHash(array $variantFields, Product $product)
    {
        $propertyAccessor = new PropertyAccessor();

        $fields = [];
        foreach ($variantFields as $fieldName) {
            if ($propertyAccessor->isReadable($product, $fieldName)) {
                $fields[$fieldName] = $propertyAccessor->getValue($product, $fieldName);
            }
        }

        return md5(json_encode($fields));
    }
}
