<?php

namespace Oro\Bundle\SEOBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generate sitemaps to all websites
 */
class GenerateSitemapTopic extends AbstractTopic implements JobAwareTopicInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.seo.generate_sitemap';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Generates sitemaps for all websites';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        return self::getName();
    }
}
