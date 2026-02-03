<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for scheduling batch resizing of all product images with optional chunkSize, dimensions and force options.
 */
class ResizeAllProductImagesTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const string NAME = 'oro_product.image_resize_all';
    public const string PRIORITY = MessagePriority::LOW;

    public const string FORCE = 'force';
    public const string DIMENSIONS = 'dimensions';

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Schedules batch resizing of all product images with optional granularization.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::FORCE)
            ->allowedTypes('bool')
            ->default(false);

        $resolver
            ->define(self::DIMENSIONS)
            ->allowedTypes('array', 'null')
            ->default(null);
    }

    #[\Override]
    public function getDefaultPriority(string $queueName): string
    {
        return static::PRIORITY;
    }

    public function createJobName($messageBody): string
    {
        return static::NAME;
    }
}
