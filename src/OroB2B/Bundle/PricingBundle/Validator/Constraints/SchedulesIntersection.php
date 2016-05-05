<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SchedulesIntersection extends Constraint
{
    const ALIAS = 'orob2b_pricing_schedules_intersection_validator';

    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.price_list.schedules_intersection.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }
}
