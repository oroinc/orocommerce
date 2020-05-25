<?php

namespace Oro\Bundle\VisibilityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a visibility level is applicable for a specific visibility rule.
 */
class VisibilityType extends Constraint
{
    public $message = 'oro.visibility.wrong_type.message';

    /**
     * The path to the visibility field.
     *
     * @var string
     */
    public $path = 'visibility';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
