<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate logical expressions used in product rules.
 *
 * This constraint validates that logical expressions follow the correct syntax and do not contain disallowed
 * logical operators, ensuring that product rules and conditions are properly formed and executable.
 */
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

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_product.validator_constraints.logical_expression_validator';
    }
}
