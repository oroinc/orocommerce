<?php

namespace OroB2B\Bundle\AccountBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class VisibilityChangeSet extends Constraint
{
    /** @var string */
    public $invalidDataMessage = 'orob2b.account.catalog.visibility.validation.invalid_data';

    /** @var string */
    public $invalidFormatMessage = 'orob2b.account.catalog.visibility.validation.invalid_format';

    /** @var string */
    public $entityClass;

    /**
     * {@inheritdoc}
     */
    public function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b.account.catalog.visibility.schange_set.validatior';
    }
}
