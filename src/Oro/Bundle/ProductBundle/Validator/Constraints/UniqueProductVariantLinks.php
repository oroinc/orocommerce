<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for check unique configuration simple products in configurable product
 */
class UniqueProductVariantLinks extends Constraint
{
    /** @var string */
    public $uniqueRequiredMessage = 'oro.product.product_variant_links.unique_variants_combination.message';

    /** @var string */
    public $property;

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
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
