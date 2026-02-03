<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for scheduling batch resizing of all product images with required jobId and optional dimensions and imageIds.
 */
class ResizeProductImageChunkTopic extends AbstractTopic
{
    public const NAME = 'oro_product.image_resize_chunk';
    public const PRIORITY = MessagePriority::LOW;

    public const JOB_ID = 'jobId';
    public const FORCE = 'force';
    public const IMAGE_IDS = 'imageIds';
    public const DIMENSIONS = 'dimensions';

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Schedules batch resizing of chunk product images ' .
            'with optional granulization to prevent RabbitMQ overload.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(self::JOB_ID)
            ->setAllowedTypes(self::JOB_ID, 'int');

        $resolver
            ->define(self::FORCE)
            ->allowedTypes('bool')
            ->default(false);

        $resolver
            ->define(self::DIMENSIONS)
            ->allowedTypes('array', 'null')
            ->default(null);

        $resolver
            ->define(self::IMAGE_IDS)
            ->allowedTypes('array', 'null')
            ->default(null);
    }

    #[\Override]
    public function getDefaultPriority(string $queueName): string
    {
        return self::PRIORITY;
    }
}
