<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator checks whether adding a shipping service is correct for a selected country.
 */
class CountryShippingServicesValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CountryShippingServices) {
            throw new UnexpectedTypeException($constraint, CountryShippingServices::class);
        }

        if (!$value instanceof UPSTransport) {
            return;
        }

        $settingsCountry = $value->getUpsCountry();
        if (!$settingsCountry) {
            return;
        }

        foreach ($value->getApplicableShippingServices() as $applicableShippingService) {
            $shippingServiceCountry = $applicableShippingService->getCountry();
            if ($shippingServiceCountry !== $settingsCountry) {
                $this->context
                    ->buildViolation($constraint->message, [
                        '%shipping_service%'         => (string)$applicableShippingService,
                        '%settings_country%'         => (string)$settingsCountry,
                        '%shipping_service_country%' => (string)$shippingServiceCountry,
                    ])
                    ->atPath('applicableShippingServices')
                    ->addViolation();
            }
        }
    }
}
