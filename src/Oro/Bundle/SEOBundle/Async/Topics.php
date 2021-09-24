<?php

namespace Oro\Bundle\SEOBundle\Async;

/**
 * MQ topics used during sitemaps generation.
 */
class Topics
{
    public const GENERATE_SITEMAP = 'oro.seo.generate_sitemap';
    public const GENERATE_SITEMAP_INDEX = 'oro.seo.generate_sitemap_index';
    public const GENERATE_SITEMAP_INDEX_ST = 'oro.seo.generate_sitemap_index.single_thread';
    public const GENERATE_SITEMAP_INDEX_BY_WEBSITE = 'oro.seo.generate_sitemap_index_by_website';
    public const GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE  = 'oro.seo.generate_sitemap_by_website_and_type';
    public const GENERATE_SITEMAP_MOVE_GENERATED_FILES = 'oro.seo.generate_sitemap_move_generated_files';
}
