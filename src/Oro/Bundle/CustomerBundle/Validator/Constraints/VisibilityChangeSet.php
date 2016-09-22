<?php

namespace Oro\Bundle\CustomerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class VisibilityChangeSet extends Constraint
{
    /** @var string */
    public $invalidDataMessage ='oro.customer.category.visibility.message.invalid_data';

    /** @var string */
    public $entityClass;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro.customer.catalog.visibility.change_set.validatior';
    }
}
