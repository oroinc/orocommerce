<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Product class validation constraint for validation allowed product kit shipping calculation methods
 */
class ProductKitShippingCalculationMethod extends Constraint
{
    public const METHOD_NOT_ALLOWED_ERROR = '7721e830-5a95-4d87-9847-15966c74a648';

    protected static $errorNames = [
        self::METHOD_NOT_ALLOWED_ERROR => 'METHOD_NOT_ALLOWED_ERROR',
    ];

    public string $message = 'oro.product.shipping_calculation_method.not_supported';

    /** @var string[] $allowedShippingCalculationMethods */
    public array $allowedShippingCalculationMethods = [];

    #[\Override]
    public function getRequiredOptions(): array
    {
        return ['allowedShippingCalculationMethods'];
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
