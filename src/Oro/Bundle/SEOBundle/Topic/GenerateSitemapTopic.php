<?php

namespace Oro\Bundle\SEOBundle\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generate sitemaps to all websites
 */
class GenerateSitemapTopic extends AbstractTopic
{
    private const TOPIC_NAME = 'oro.seo.generate_sitemap';
    private const TOPIC_DESCRIPTION = 'Generates sitemaps for all websites';

    public static function getName(): string
    {
        return self::TOPIC_NAME;
    }

    public static function getDescription(): string
    {
        return self::TOPIC_DESCRIPTION;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
