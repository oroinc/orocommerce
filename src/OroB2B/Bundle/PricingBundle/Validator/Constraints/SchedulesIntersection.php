<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SchedulesIntersection extends Constraint
{
    const ALIAS = 'orob2b_pricing_schedules_intersection_validator';

    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.price_list.schedules_intersection.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }
}
