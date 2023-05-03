<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to initiate web catalog content node cache calculation.
 */
class WebCatalogCalculateContentNodeCacheTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const CONTENT_NODE_ID = 'contentNodeId';

    public static function getName(): string
    {
        return 'oro.web_catalog.calculate_cache.content_node';
    }

    public static function getDescription(): string
    {
        return 'Initiate web catalog content node cache calculation.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::CONTENT_NODE_ID)
            ->required()
            ->allowedTypes('int');
    }

    public function createJobName($messageBody): string
    {
        return sprintf('%s:%s', self::getName(), $messageBody[self::CONTENT_NODE_ID]);
    }
}
