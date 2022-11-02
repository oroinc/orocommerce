<?php

namespace Oro\Bundle\VisibilityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that each element of a visibility change set
 * is assigned to an entity of a specific type.
 */
class VisibilityChangeSet extends Constraint
{
    public $message = 'oro.visibility.category.visibility.message.invalid_data';

    /** @var string */
    public $entityClass;
}
