<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ProductBundle\Entity\Product;

class UniqueProductVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_unique_variant_links';

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

        if (!$value->isConfigurable()) {
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
        $fields = [];
        foreach ($variantFields as $fieldName) {
            if ($this->propertyAccessor->isReadable($product, $fieldName)) {
                $fieldValue = $this->propertyAccessor->getValue($product, $fieldName);
                $fields[$fieldName] = $fieldValue;

                if (is_object($fieldValue) && method_exists($fieldValue, '__toString')) {
                    $fields[$fieldName] = (string) $fieldValue;
                }
            }
        }

        return md5(json_encode($fields));
    }
}
