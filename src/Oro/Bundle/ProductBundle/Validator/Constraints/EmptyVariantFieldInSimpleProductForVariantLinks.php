<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EmptyVariantFieldInSimpleProductForVariantLinks extends Constraint
{
    public $message = 'oro.product.product_variant_field.unique_variant_links_when_empty_variant_field_in_simple';

    #[\Override]
    public function validatedBy(): string
    {
        return EmptyVariantFieldInSimpleProductForVariantLinksValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
