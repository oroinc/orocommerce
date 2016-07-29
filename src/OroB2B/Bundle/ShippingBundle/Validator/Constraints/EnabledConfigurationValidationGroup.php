<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EnabledConfigurationValidationGroup extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.shipping.shippingrule.configuration.count.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_shipping_enabled_configuration_validation_group_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
