<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint that checks for a product line item if there is enough quantity in inventory level.
 */
class HasEnoughInventoryLevel extends Constraint
{
    public const NOT_ENOUGH_QUANTITY = '771e67bf-6f27-4eb8-a60e-4db2aa29c144';

    protected static $errorNames = [
        self::NOT_ENOUGH_QUANTITY => 'NOT_ENOUGH_QUANTITY',
    ];

    public string $message = 'oro.inventory.has_enough_inventory_level.not_enough_quantity';

    #[\Override]
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
