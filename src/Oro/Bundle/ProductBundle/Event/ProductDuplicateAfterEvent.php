<?php

namespace Oro\Bundle\ProductBundle\Event;

class ProductDuplicateAfterEvent extends AbstractProductDuplicateEvent
{
    public const NAME = 'oro_product.product.duplicate.after';
}
