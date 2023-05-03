<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as DoctrineUniqueEntityConstraint;

/**
 * The constraint for the {@see UniqueEntityValidator}.
 */
class UniqueEntity extends DoctrineUniqueEntityConstraint
{
    public $service = 'oro_validation.validator_constraints.unique_entity';

    public $message = 'This value is used. Unique constraint: unique_key';

    public ?bool $buildViolationAtEntityLevel = true;
}
