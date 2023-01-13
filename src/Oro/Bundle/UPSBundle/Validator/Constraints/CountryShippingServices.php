<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether adding a shipping service is correct for a selected country.
 */
class CountryShippingServices extends Constraint
{
    public string $message = 'oro.ups.settings.shipping_service.wrong_country.message';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
