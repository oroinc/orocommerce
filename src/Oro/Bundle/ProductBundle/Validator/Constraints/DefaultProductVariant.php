<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that selected default product variant is one of the selected product variants
 * @Annotation
 */
#[\Attribute]
class DefaultProductVariant extends Constraint
{
    public string $message = 'oro.product.product_variant_links.default_variant_is_not_product_variant.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
