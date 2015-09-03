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
     * @var PropertyAccessor
     */
    private $accessor;

    public function __construct()
    {
        $this->accessor = new PropertyAccessor();
    }

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->hasVariants()) {
            return;
        }

        $variantHashes = [];
        $variantFields = $value->getVariantFields();

        foreach ($value->getVariantLinks() as $variantLink) {
            $variantHashes[] = $this->getVariantFieldsHash($variantFields, $variantLink->getProduct());
        }

        if (count($variantHashes) !== count(array_unique($variantHashes))) {
            $this->context->addViolation($constraint->variantFieldValueCombinationsShouldBeUnique);
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
            $fields[$fieldName] = $this->accessor->getValue($product, $fieldName);
        }

        return md5(json_encode($fields));
    }
}
