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

        if (!$value->getVariantFields()) {
            return;
        }

        $this->validateUniqueVariantLinks($value, $constraint);
    }

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks $constraint
     */
    private function validateUniqueVariantLinks(Product $value, UniqueProductVariantLinks $constraint)
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
