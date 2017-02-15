<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EmptyVariantFieldInSimpleProductForVariantLinks extends Constraint
{
    public $message = 'oro.product.product_variant_field.unique_variant_links_when_empty_variant_field_in_simple';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return EmptyVariantFieldInSimpleProductForVariantLinksValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
