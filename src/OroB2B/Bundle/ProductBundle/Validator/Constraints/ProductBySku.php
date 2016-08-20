<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductBySku extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_by_sku.not_found';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return 'orob2b_product_product_by_sku_validator';
    }
}
