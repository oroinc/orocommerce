<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueProductVariantLinks extends Constraint
{
    public $variantFieldValueCombinationsShouldBeUnique =
        'orob2b.product.validators.product_variant_links.unique_variants_combination.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UniqueProductVariantLinksValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
