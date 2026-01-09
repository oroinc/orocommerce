<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate that a product exists for a given SKU.
 *
 * This constraint checks that a provided SKU corresponds to an existing product in the system,
 * commonly used in import operations and quick add functionality.
 */
class ProductBySku extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_by_sku.not_found';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_product_product_by_sku_validator';
    }
}
