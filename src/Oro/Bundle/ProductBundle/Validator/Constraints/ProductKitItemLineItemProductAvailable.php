<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking if the product specified in a kit item line item is allowed for
 * the related {@see ProductKitItem}.
 */
class ProductKitItemLineItemProductAvailable extends Constraint
{
    public const PRODUCT_NOT_ALLOWED = '3e30c094-0c7c-4266-9130-0f6e0df569cf';

    protected static $errorNames = [self::PRODUCT_NOT_ALLOWED => 'PRODUCT_NOT_ALLOWED'];

    public string $message = 'oro.product.productkititemlineitem.product.not_available.message';

    /**
     * @var array<string> Array of fields that should be updated to trigger this constraint.
     */
    public array $ifChanged = [];

    /**
     * @var array|string[] Validation groups to use when checking if a kit item product is available.
     */
    public array $availabilityValidationGroups = [];
}
