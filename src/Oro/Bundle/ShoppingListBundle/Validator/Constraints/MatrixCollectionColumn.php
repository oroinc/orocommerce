<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MatrixCollectionColumn extends Constraint
{
    /**
     * @var string
     */
    public $messageOnProductUnavailable = 'oro.matrixgrid.validate.product_unavailable';
    public $messageOnNonValidPrecision = 'oro.matrixgrid.validate.non_valid_precision';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
