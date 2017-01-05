<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingService extends Constraint
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
}
