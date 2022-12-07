<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for maximum and minimum quantity to order limits.
 */
class QuantityToOrder extends Constraint
{
    public const GREATER_THAN_MAX_LIMIT = 'd517ba2f-a05a-4a8c-8e53-b2b863d9c2bc';
    public const LESS_THAN_MIN_LIMIT = 'dc6cedd2-b459-4705-83e8-5cf00a21dfac';

    protected static $errorNames = [
        self::GREATER_THAN_MAX_LIMIT => 'GREATER_THAN_MAX_LIMIT',
        self::LESS_THAN_MIN_LIMIT => 'LESS_THAN_MIN_LIMIT',
    ];

    public string $minMessage = '';
    public string $maxMessage = '';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
