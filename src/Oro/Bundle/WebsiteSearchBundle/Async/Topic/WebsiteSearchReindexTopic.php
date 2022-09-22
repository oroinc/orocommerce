<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\Topic;

use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to reindex the specified entities by classes and ids with optional granulating.
 */
class WebsiteSearchReindexTopic extends AbstractTopic
{
    public const NAME = 'oro.website.search.indexer.reindex';
    public const PRIORITY = MessagePriority::LOW;

    private IndexerInputValidator $indexerInputValidator;

    public function __construct(IndexerInputValidator $indexerInputValidator)
    {
        $this->indexerInputValidator = $indexerInputValidator;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Reindex the specified entities by classes and ids with optional granulating.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('jobId')
            ->setAllowedTypes('jobId', ['int', 'null']);

        $this->indexerInputValidator->configureClassOptions($resolver);
        $this->indexerInputValidator->configureGranulizeOptions($resolver);
        $this->indexerInputValidator->configureContextOptions($resolver);
    }

    public function getDefaultPriority(string $queueName): string
    {
        return self::PRIORITY;
    }
}
