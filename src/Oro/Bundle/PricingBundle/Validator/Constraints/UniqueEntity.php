<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueEntity extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.product_price.unique_entity.message';

    /**
     * @var array
     */
    public $fields;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_pricing_unique_entity_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
