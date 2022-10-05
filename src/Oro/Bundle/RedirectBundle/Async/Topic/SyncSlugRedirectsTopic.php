<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to synchronize the redirects of the specified slug.
 */
class SyncSlugRedirectsTopic extends AbstractTopic
{
    public const SLUG_ID = 'slugId';

    public static function getName(): string
    {
        return 'oro.redirect.generate_slug_redirects';
    }

    public static function getDescription(): string
    {
        return 'Synchronize the redirects of the specified slug.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(self::SLUG_ID)
            ->setAllowedTypes(self::SLUG_ID, ['int', 'string']);
    }
}
