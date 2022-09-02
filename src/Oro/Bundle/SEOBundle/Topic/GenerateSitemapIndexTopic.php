<?php

namespace Oro\Bundle\SEOBundle\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generate sitemap index
 */
class GenerateSitemapIndexTopic extends AbstractTopic
{
    public const JOB_ID = 'jobId';
    public const VERSION = 'version';
    public const WEBSITE_IDS = 'websiteIds';

    private const TOPIC_NAME = 'oro.seo.generate_sitemap_index';
    private const TOPIC_DESCRIPTION = 'Generates sitemaps index';

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
        $resolver
            ->setDefined(self::JOB_ID)
            ->setRequired([
                self::JOB_ID,
                self::VERSION,
                self::WEBSITE_IDS,
            ])
            ->setAllowedTypes(self::JOB_ID, 'int')
            ->setAllowedTypes(self::VERSION, 'int')
            ->setAllowedTypes(self::WEBSITE_IDS, 'array');
    }
}
