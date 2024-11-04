<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NotEmptyConfigurableAttributes extends Constraint
{
    /** @var string */
    public $message = 'oro.product.attribute_family.empty_configurable_attributes.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return NotEmptyConfigurableAttributesValidator::ALIAS;
    }
}
