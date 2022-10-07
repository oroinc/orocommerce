<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to fill Slug URL caches for the specified entities within a job.
 */
class CalculateSlugCacheJobAwareTopic extends AbstractTopic
{
    public const NAME = 'oro.redirect.calculate_cache.process_job';
    public const JOB_ID = 'jobId';

    private CalculateSlugCacheTopic $innerTopic;

    public function __construct(CalculateSlugCacheTopic $innerTopic)
    {
        $this->innerTopic = $innerTopic;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Fill Slug URL caches for the specified entities within a job.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->innerTopic->configureMessageBody($resolver);

        $resolver
            ->setDefined(self::JOB_ID)
            ->setAllowedTypes(self::JOB_ID, 'int');
    }
}
