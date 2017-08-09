<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as DoctrineUniqueEntityConstraint;

class UniqueEntity extends DoctrineUniqueEntityConstraint
{
    public $service = 'oro_validation.validator_constraints.unique_entity';

    public $message = 'This value is used. Unique constraint: unique_key';

    /**
     * @var bool
     */
    public $buildViolationAtEntityLevel = true;

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
