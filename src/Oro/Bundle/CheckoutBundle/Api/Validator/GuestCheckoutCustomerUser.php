<?php

namespace Oro\Bundle\CheckoutBundle\Api\Validator;

use Oro\Bundle\ApiBundle\Validator\Constraints\ConstraintWithStatusCodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that a visitor has an access to a customer user
 * when the checkout feature is enabled for visitors.
 */
class GuestCheckoutCustomerUser extends Constraint implements ConstraintWithStatusCodeInterface
{
    public string $message = 'oro.api.form.no_access';

    #[\Override]
    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
