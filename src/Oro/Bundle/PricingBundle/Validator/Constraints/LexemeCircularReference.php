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

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_pricing.validator_constraints.lexeme_circular_reference_validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
