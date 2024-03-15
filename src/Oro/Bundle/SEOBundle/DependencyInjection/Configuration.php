<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_seo';
    public const ROBOTS_TXT_TEMPLATE = 'sitemap_robots_txt_template';

    public const CHANGEFREQ_ALWAYS = 'always';
    public const CHANGEFREQ_HOURLY = 'hourly';
    public const CHANGEFREQ_DAILY = 'daily';
    public const CHANGEFREQ_WEEKLY = 'weekly';
    public const CHANGEFREQ_MONTHLY = 'monthly';
    public const CHANGEFREQ_YEARLY = 'yearly';
    public const CHANGEFREQ_NEVER = 'never';

    public const DEFAULT_PRIORITY = 0.5;

    public const DEFAULT_CRON_DEFINITION = '0 0 * * *';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode    = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'sitemap_changefreq_product' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_priority_product' => ['value' => self::DEFAULT_PRIORITY],
                'sitemap_changefreq_category' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_priority_category' => ['value' => self::DEFAULT_PRIORITY],
                'sitemap_changefreq_cms_page' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_priority_cms_page' => ['value' => self::DEFAULT_PRIORITY],
                'sitemap_cron_definition' => ['value' => self::DEFAULT_CRON_DEFINITION],
                'sitemap_exclude_landing_pages' => ['value' => true],
                'sitemap_include_landing_pages_not_in_web_catalog' => ['value' => false],
                self::ROBOTS_TXT_TEMPLATE => ['type' => 'string', 'value' => ''],
            ]
        );

        return $treeBuilder;
    }
}
