<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const CHANGEFREQ_ALWAYS = 'always';
    const CHANGEFREQ_HOURLY = 'hourly';
    const CHANGEFREQ_DAILY = 'daily';
    const CHANGEFREQ_WEEKLY = 'weekly';
    const CHANGEFREQ_MONTHLY = 'monthly';
    const CHANGEFREQ_YEARLY = 'yearly';
    const CHANGEFREQ_NEVER = 'never';

    const DEFAULT_PRIORITY = 0.5;

    const DEFAULT_CRON_DEFINITION = '0 0 * * *';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_seo');

        SettingsBuilder::append(
            $rootNode,
            [
                'sitemap_changefreq_default' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_changefreq_product' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_priority_product' => ['value' => self::DEFAULT_PRIORITY],
                'sitemap_changefreq_category' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_priority_category' => ['value' => self::DEFAULT_PRIORITY],
                'sitemap_changefreq_page' => ['value' => self::CHANGEFREQ_DAILY],
                'sitemap_priority_page' => ['value' => self::DEFAULT_PRIORITY],
                'sitemap_cron_definition' => ['value' => self::DEFAULT_CRON_DEFINITION],
            ]
        );

        return $treeBuilder;
    }
}
