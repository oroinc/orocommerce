<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for matrix collection column data in configurable product order forms.
 *
 * This constraint validates individual cells in the matrix order form, ensuring that:
 * - Products are available when quantities are specified
 * - Quantity values respect the unit precision configured for the product
 *
 * The constraint is applied at the class level to the MatrixCollectionColumn model and is used
 * to provide real-time validation feedback when customers fill out matrix forms
 * for ordering multiple variants of configurable products.
 */
class MatrixCollectionColumn extends Constraint
{
    /**
     * @var string
     */
    public $messageOnProductUnavailable = 'oro.product_unavailable';
    public $messageOnNonValidPrecision  = 'oro.non_valid_precision';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
