<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that {@see ProductKitItemLabel} string not empty if not have fallback
 */
class NotBlankProductKitItemLabelString extends Constraint
{
    public $message = 'This value should not be blank.';

    #[\Override]
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
