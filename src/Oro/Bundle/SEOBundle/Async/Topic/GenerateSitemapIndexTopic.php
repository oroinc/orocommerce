<?php

namespace Oro\Bundle\SEOBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generating the sitemap index file.
 */
class GenerateSitemapIndexTopic extends AbstractTopic
{
    public const JOB_ID = 'jobId';
    public const VERSION = 'version';
    public const WEBSITE_IDS = 'websiteIds';

    public static function getName(): string
    {
        return 'oro.seo.generate_sitemap_index';
    }

    public static function getDescription(): string
    {
        return 'Generates sitemaps index file';
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
