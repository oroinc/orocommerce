<?php

namespace Oro\Bundle\CustomerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueAddressDefaultTypes extends Constraint
{
    /** @var string */
    public $message = 'Several addresses have the same default type {{ types }}.';
}
