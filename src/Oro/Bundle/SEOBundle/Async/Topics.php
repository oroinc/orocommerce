<?php

namespace Oro\Bundle\SEOBundle\Async;

/**
 * MQ topics used during sitemaps generation.
 * @deprecated Will be removed in 5.1, use MQ topic classes instead:
 * {@see \Oro\Component\MessageQueue\Topic\TopicInterface}
 */
class Topics
{
    public const GENERATE_SITEMAP = 'oro.seo.generate_sitemap';
    public const GENERATE_SITEMAP_INDEX = 'oro.seo.generate_sitemap_index';
    public const GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE  = 'oro.seo.generate_sitemap_by_website_and_type';
}
