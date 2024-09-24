<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint that checks for a product line item is upcoming.
 */
class IsUpcoming extends Constraint
{
    public const IS_UPCOMING = '8c9ce584-4806-4737-9728-e2483f687863';

    protected static $errorNames = [
        self::IS_UPCOMING => 'IS_UPCOMING',
    ];

    public string $message = '';

    #[\Override]
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
