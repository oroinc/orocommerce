<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to generate Slug URLs for the specified entities within a job.
 */
class GenerateDirectUrlForEntitiesJobAwareTopic extends AbstractTopic
{
    public const NAME = 'oro.redirect.job.generate_direct_url.entity';
    public const JOB_ID = 'jobId';

    private GenerateDirectUrlForEntitiesTopic $innerTopic;

    public function __construct(GenerateDirectUrlForEntitiesTopic $innerTopic)
    {
        $this->innerTopic = $innerTopic;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Generate Slug URLs for the specified entities within a job.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->innerTopic->configureMessageBody($resolver);

        $resolver
            ->setDefined(self::JOB_ID)
            ->setAllowedTypes(self::JOB_ID, 'int');
    }
}
