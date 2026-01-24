<?php

namespace Oro\Bundle\ProductBundle\Event;

/**
 * Dispatched after a product has been duplicated.
 *
 * This event allows listeners to perform post-duplication tasks such as copying related entities,
 * adjusting duplicated data, or triggering additional business logic after the new product has been created
 * from the source product.
 */
class ProductDuplicateAfterEvent extends AbstractProductDuplicateEvent
{
    const NAME = 'oro_product.product.duplicate.after';
}
