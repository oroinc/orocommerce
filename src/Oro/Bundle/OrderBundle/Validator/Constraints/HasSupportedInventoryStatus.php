<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint that checks for a product if it has supported inventory status.
 */
class HasSupportedInventoryStatus extends Constraint
{
    public string $configurationPath = 'oro_order.frontend_product_visibility';

    public string $message = 'oro.order.inventory_status.not_supported';
}
