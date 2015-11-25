<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductVariantLinksByProductSku extends Constraint
{
    /** @var string */
    public $message = 'orob2b.product.product_variant_links.sku.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ProductVariantLinkByProductSkuValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
