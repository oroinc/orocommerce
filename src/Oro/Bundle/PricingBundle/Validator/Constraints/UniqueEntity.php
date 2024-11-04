<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a product does not have duplication of product prices.
 */
class UniqueEntity extends Constraint
{
    public string $message = 'oro.pricing.validators.product_price.unique_entity.message';

    public array $fields;

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
