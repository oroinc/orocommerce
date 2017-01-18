<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MatrixCollectionColumn extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.matrixgrid.product_unavailable';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
