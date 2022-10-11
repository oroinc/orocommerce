<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to fill Slug URL caches for the specified entities.
 */
class CalculateSlugCacheTopic extends AbstractTopic
{
    public const NAME = 'oro.redirect.calculate_cache';

    private DirectUrlTopicHelper $directUrlCommonTopicHelper;

    public function __construct(DirectUrlTopicHelper $directUrlCommonTopicHelper)
    {
        $this->directUrlCommonTopicHelper = $directUrlCommonTopicHelper;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Fill Slug URL caches for the specified entities.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->directUrlCommonTopicHelper->configureIdOption($resolver);
        $this->directUrlCommonTopicHelper->configureEntityClassOption($resolver);
        $this->directUrlCommonTopicHelper->configureRedirectOption($resolver);
    }
}
