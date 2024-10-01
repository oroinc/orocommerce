<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to resolve visibility for a category when its position is changed.
 */
class VisibilityOnChangeCategoryPositionTopic extends AbstractTopic
{
    public const NAME = 'oro_visibility.visibility.category_position_change';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Resolve visibility for a category when its position is changed.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('id')
            ->setAllowedTypes('id', ['int', 'null']);
    }
}
