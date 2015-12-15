<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class UniqueProductVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_unique_variant_links';

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getHasVariants()) {
            return;
        }

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
            $this->context->addViolation($constraint->message);
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
