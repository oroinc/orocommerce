<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that one of fields should be blank.
 */
class BlankOneOf extends Constraint
{
    public string $message = 'One of fields: %fields% should be blank';

    public array $fields = [];

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
