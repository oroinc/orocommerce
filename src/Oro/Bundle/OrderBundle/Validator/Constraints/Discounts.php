<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Discounts extends Constraint
{
    /**
     * @var string
     */
    public $errorMessage = 'oro.order.discounts.sum.error.label';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
