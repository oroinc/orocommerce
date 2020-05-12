<?php

namespace Oro\Bundle\VisibilityBundle\Async;

/**
 * Visibility Bundle related Message Queue Topics.
 */
final class Topics
{
    public const CHANGE_PRODUCT_CATEGORY    = 'oro_visibility.visibility.change_product_category';
    public const RESOLVE_PRODUCT_VISIBILITY = 'oro_visibility.visibility.resolve_product_visibility';
    public const CHANGE_CATEGORY_VISIBILITY = 'oro_visibility.visibility.change_category_visibility';
    public const CHANGE_CUSTOMER            = 'oro_visibility.visibility.change_customer';
    public const CATEGORY_POSITION_CHANGE   = 'oro_visibility.visibility.category_position_change';
    public const CATEGORY_REMOVE            = 'oro_visibility.visibility.category_remove';
}
