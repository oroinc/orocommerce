<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QuickAddRowCollection extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return QuickAddRowCollectionValidator::ALIAS;
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
