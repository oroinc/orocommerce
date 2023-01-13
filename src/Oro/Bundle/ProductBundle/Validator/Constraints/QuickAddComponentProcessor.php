<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for quick add component processor.
 */
class QuickAddComponentProcessor extends Constraint
{
    public const NOT_AVAILABLE_PROCESSOR = 'aa62f4b2-826e-4ddf-8e67-0f5b87d96aaa';

    public string $message = 'oro.product.frontend.quick_add.validation.component_not_accessible';

    protected static $errorNames = [
        self::NOT_AVAILABLE_PROCESSOR => 'NOT_AVAILABLE_PROCESSOR',
    ];
}
