<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ShippingRuleEnable extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.shipping.shippingrule.enabled.message';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
