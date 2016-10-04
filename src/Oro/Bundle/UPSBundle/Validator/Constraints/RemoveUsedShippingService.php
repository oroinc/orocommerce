<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingService extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.ups.transport.shipping_service.used';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return RemoveUsedShippingServiceValidator::ALIAS;
    }
}
