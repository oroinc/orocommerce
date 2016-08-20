<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniquePriceList extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.price_list.unique_price_list.message';

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
