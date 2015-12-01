<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;

class ProductVariantLinkByProductSkuValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_variant_links_by_product_sku';

    /**
     * @param ProductVariantLink $value
     * @param ProductVariantLinkByProductSku|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value->getProduct() === null) {
            $this->context->addViolation($constraint->message);
        }
    }
}
