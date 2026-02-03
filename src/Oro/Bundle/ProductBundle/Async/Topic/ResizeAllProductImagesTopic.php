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
    public const NAME = 'oro_product.image_resize_all';
    public const PRIORITY = MessagePriority::LOW;

    public const FORCE = 'force';
    public const DIMENSIONS = 'dimensions';

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Schedules batch resizing of all product images ' .
            'with optional granulization to prevent RabbitMQ overload.';
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

    #[\Override]
    public function createJobName($messageBody): string
    {
        return static::NAME;
    }
}
