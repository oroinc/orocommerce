<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to resolve visibility when a category is removed.
 */
class VisibilityOnRemoveCategoryTopic extends AbstractTopic
{
    public const NAME = 'oro_visibility.visibility.category_remove';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Resolve visibility when a category is removed';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('id')
            ->setAllowedTypes('id', 'int');
    }
}
