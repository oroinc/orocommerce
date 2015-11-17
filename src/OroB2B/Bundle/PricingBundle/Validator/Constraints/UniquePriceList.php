<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniquePriceList extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Duplicate price lists: priceLists';

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
