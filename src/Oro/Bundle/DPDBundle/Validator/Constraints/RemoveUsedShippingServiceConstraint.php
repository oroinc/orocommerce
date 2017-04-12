<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.dpd.transport.shipping_service.used';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return RemoveUsedShippingServiceValidator::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
