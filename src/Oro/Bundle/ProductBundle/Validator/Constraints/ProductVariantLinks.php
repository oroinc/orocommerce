<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductVariantLinks extends Constraint
{
    /** @var string */
    public $variantFieldRequiredMessage = 'oro.product.product_variant_links.variant_field_required.message';

    /** @var string */
    public $variantLinkHasNoFilledFieldMessage = 'oro.product.product_variant_links.has_no_filled_field.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ProductVariantLinksValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
