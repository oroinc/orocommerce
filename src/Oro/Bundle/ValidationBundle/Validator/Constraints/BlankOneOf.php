<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class BlankOneOf extends Constraint
{
    /**
     * @var string
     */
    public $message = 'One of fields: %fields% should be blank';

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
}
