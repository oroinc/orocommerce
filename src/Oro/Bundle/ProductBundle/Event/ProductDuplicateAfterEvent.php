<?php

namespace Oro\Bundle\ProductBundle\Event;

class ProductDuplicateAfterEvent extends AbstractProductDuplicateEvent
{
    const NAME = 'oro_product.product.duplicate.after';
}
