<?php

namespace Oro\Bundle\CustomerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ScopeWithCustomerGroupAndCustomer extends Constraint
{
    /** @var string */
    public $message = 'Should be chosen only one field. Or Customer Group or Customer.';
}
