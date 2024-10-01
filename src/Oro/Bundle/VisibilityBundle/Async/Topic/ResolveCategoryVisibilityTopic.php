<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

/**
 * A topic to resolve visibility for a category.
 */
class ResolveCategoryVisibilityTopic extends AbstractResolveVisibilityTopic
{
    public const NAME = 'oro_visibility.visibility.change_category_visibility';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Resolve visibility for a category.';
    }
}
