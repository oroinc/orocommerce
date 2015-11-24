<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QuickAddRowCollection extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return QuickAddRowCollectionValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
