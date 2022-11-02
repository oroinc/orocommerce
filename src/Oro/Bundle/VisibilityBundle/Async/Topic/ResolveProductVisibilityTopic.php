<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

/**
 * A topic to resolve visibility for a product.
 */
class ResolveProductVisibilityTopic extends AbstractResolveVisibilityTopic
{
    public const NAME = 'oro_visibility.visibility.resolve_product_visibility';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Resolve visibility for a product.';
    }
}
