<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint that checks for a product line item if its inventory level is running low.
 */
class IsLowInventoryLevel extends Constraint
{
    public const LOW_INVENTORY_LEVEL = '19064817-bc6b-4f72-99d1-9ee87a5b76ae';

    protected static $errorNames = [
        self::LOW_INVENTORY_LEVEL => 'LOW_INVENTORY_LEVEL',
    ];

    public string $message = '';

    #[\Override]
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
