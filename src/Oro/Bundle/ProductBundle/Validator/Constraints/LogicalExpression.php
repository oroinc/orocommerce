<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LogicalExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.validators.logical_expression.message';

    /**
     * @var string
     */
    public $messageDisallowedLogicalExpression = 'oro.product.validators.logical_expression_disallowed.message';

    /**
     * @var bool
     */
    public $logicalExpressionsAllowed = true;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_product.validator_constraints.logical_expression_validator';
    }
}
