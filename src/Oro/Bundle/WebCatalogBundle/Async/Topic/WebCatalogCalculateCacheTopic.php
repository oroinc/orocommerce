<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to initiate web catalog cache calculation.
 */
class WebCatalogCalculateCacheTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const WEB_CATALOG_ID = 'webCatalogId';

    public static function getName(): string
    {
        return 'oro.web_catalog.calculate_cache';
    }

    public static function getDescription(): string
    {
        return 'Initiate web catalog cache calculation.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::WEB_CATALOG_ID)
            ->required()
            ->allowedTypes('int', 'string')
            ->normalize(static fn (Options $options, $value) => (int)$value);
    }

    public function createJobName($messageBody): string
    {
        return sprintf('%s:%s', self::getName(), $messageBody[self::WEB_CATALOG_ID]);
    }
}
