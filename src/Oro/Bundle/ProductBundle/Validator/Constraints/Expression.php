<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_product.validator_constraints.expression_validator';
    }
}
