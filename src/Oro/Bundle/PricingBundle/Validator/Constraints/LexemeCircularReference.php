<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for detecting circular references in price rule lexemes.
 *
 * Validates that price rule expressions do not contain circular references that would
 * cause infinite loops during price calculation.
 */
class LexemeCircularReference extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.circular_reference.message';

    /**
     * @var string
     */
    public $invalidNodeMessage = 'oro.pricing.validators.circular_reference.invalid_node_message';

    /**
     * @var array
     */
    public $fields;

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_pricing.validator_constraints.lexeme_circular_reference_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
