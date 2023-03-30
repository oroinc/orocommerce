<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that {@see Product::$type} is within the allowed types list.
 */
class ProductType extends Constraint
{
    public const TYPE_NOT_ALLOWED_ERROR = '54d624a0-4ab5-4d5a-90be-857eba792f8b';

    protected static $errorNames = [
        self::TYPE_NOT_ALLOWED_ERROR => 'TYPE_NOT_ALLOWED_ERROR',
    ];

    public string $message = 'oro.product.type.not_allowed';

    /** @var string[] */
    public array $allowedTypes = [];

    public function getRequiredOptions(): array
    {
        return ['allowedTypes'];
    }
}
