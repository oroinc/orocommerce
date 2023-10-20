<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking if the product unit specified in a kit item line item
 * is allowed for the related {@see ProductKitItem}.
 */
class ProductKitItemLineItemProductUnitAvailable extends Constraint
{
    public const UNIT_NOT_ALLOWED = '1b5feb1e-d5a0-463a-9379-9a3d77bffa0c';

    protected static $errorNames = [self::UNIT_NOT_ALLOWED => 'UNIT_NOT_ALLOWED'];

    public string $message = 'oro.product.productkititemlineitem.unit.not_available.message';

    /**
     * @var array<string> Array of fields that should be updated to trigger this constraint.
     */
    public array $ifChanged = [];
}
