<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueProductVariantLinks extends Constraint
{
    public $variantFieldValueCombinationsShouldBeUnique =
        'Cannot save product variants. Variant field value combinations should be unique.';

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
