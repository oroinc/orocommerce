<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
