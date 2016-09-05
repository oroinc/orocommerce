<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NotBlankOneOf extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oh no :(';

    /**
     * @var array
     */
    public $fields = [];
    
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return NotBlankOneOfValidator::ALIAS;
    }
}
