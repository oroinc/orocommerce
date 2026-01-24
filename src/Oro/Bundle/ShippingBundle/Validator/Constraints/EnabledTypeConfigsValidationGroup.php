<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint that validates the minimum number of enabled shipping method type configurations.
 *
 * This constraint ensures that at least a specified minimum number of shipping method types are enabled
 * within a configuration group, preventing invalid shipping rules with no available shipping options.
 */
class EnabledTypeConfigsValidationGroup extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.shipping.shippingrule.type.config.count.message';

    /**
     * @var int
     */
    public $min = 1;

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_shipping_enabled_type_config_validation_group_validator';
    }
}
