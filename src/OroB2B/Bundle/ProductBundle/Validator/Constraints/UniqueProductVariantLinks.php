<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueProductVariantLinks extends Constraint
{
    /** @var string */
    public $uniqueRequiredMessage = 'orob2b.product.product_variant_links.unique_variants_combination.message';

    /** @var string */
    public $variantFieldRequiredMessage = 'orob2b.product.product_variant_links.variant_field_required.message';

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
