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
    public static function getName(): string
    {
        return 'oro.seo.generate_sitemap';
    }

    public static function getDescription(): string
    {
        return 'Generates sitemaps for all websites';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }

    public function createJobName($messageBody): string
    {
        return self::getName();
    }
}
