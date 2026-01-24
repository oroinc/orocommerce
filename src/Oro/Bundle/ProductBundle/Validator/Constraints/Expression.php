<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate product price calculation expressions.
 *
 * This constraint validates that expressions used in product price calculations contain only allowed fields
 * and follow the correct syntax, preventing invalid or dangerous expressions from being used in price calculations.
 */
class Expression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.validators.field_is_not_allowed.message';

    /**
     * @var string
     */
    public $messageAs = 'oro.product.validators.field_is_not_allowed_as.message';

    /**
     * @var string
     */
    public $divisionByZeroMessage = 'oro.product.validators.division_by_zero.message';

    /**
     * @var bool
     */
    public $withRelations = false;

    /**
     * @var bool
     */
    public $numericOnly = false;

    /**
     * @var array
     */
    public $allowedFields = [];

    /**
     * @var string
     */
    public $fieldLabel = null;

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_product.validator_constraints.expression_validator';
    }
}
