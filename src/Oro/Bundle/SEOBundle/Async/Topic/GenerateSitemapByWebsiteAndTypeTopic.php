<?php

namespace Oro\Bundle\SEOBundle\Async\Topic;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generate sitemap by website id and type
 */
class GenerateSitemapByWebsiteAndTypeTopic extends AbstractTopic
{
    public const JOB_ID = 'jobId';
    public const VERSION = 'version';
    public const WEBSITE_ID = 'websiteId';
    public const TYPE = 'type';

    private UrlItemsProviderRegistryInterface $urlItemsProviderRegistry;

    public function __construct(UrlItemsProviderRegistryInterface $urlItemsProviderRegistry)
    {
        $this->urlItemsProviderRegistry = $urlItemsProviderRegistry;
    }

    public static function getName(): string
    {
        return 'oro.seo.generate_sitemap_by_website_and_type';
    }

    public static function getDescription(): string
    {
        return 'Generates sitemap by website id and type';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                self::JOB_ID,
                self::VERSION,
                self::WEBSITE_ID,
                self::TYPE,
            ])
            ->setAllowedTypes(self::JOB_ID, 'int')
            ->setAllowedTypes(self::VERSION, 'int')
            ->setAllowedTypes(self::WEBSITE_ID, 'int')
            ->setAllowedTypes(self::TYPE, 'string')
            ->setAllowedValues(
                self::TYPE,
                array_keys($this->urlItemsProviderRegistry->getProvidersIndexedByNames())
            );
    }
}
