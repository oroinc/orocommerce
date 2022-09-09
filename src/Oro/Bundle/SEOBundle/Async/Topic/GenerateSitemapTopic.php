<?php

namespace Oro\Bundle\SEOBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generate sitemaps to all websites
 */
class GenerateSitemapTopic extends AbstractTopic
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
}
