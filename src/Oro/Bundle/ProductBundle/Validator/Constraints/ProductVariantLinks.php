<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validate that configurable product has selected configurable attributes and these attributes are filled in variants.
 */
class ProductVariantLinks extends Constraint
{
    /** @var string */
    public $property;

    /** @var string */
    public $variantFieldRequiredMessage = 'oro.product.product_variant_links.variant_field_required.message';

    /** @var string */
    public $variantLinkHasNoFilledFieldMessage = 'oro.product.product_variant_links.has_no_filled_field.message';

    /** @var string */
    public $variantLinkBelongsAnotherFamilyMessage = 'oro.product.product_variant_links.belongs_another_family.message';

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
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
