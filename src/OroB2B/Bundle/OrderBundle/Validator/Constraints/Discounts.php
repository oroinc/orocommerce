<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Discounts extends Constraint
{
    /**
     * @var string
     */
    public $errorMessage = 'orob2b.order.discounts.sum.error.label';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
