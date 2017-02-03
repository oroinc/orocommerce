<?php

namespace Oro\Bundle\InfinitePayBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CustomerRequireVatId extends Constraint
{
    public $message = 'oro.infinite_pay.validators.vat_id_required';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }
}
