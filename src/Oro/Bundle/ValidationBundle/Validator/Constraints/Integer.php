<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Integer extends Constraint implements AliasAwareConstraintInterface
{
    public const ALIAS = 'integer';

    public $message = 'This value should be integer number.';

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
