<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SEOBundle\DependencyInjection\OroSEOExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSEOExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroSEOExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'sitemap_changefreq_product' => ['value' => 'daily', 'scope' => 'app'],
                        'sitemap_priority_product' => ['value' => 0.5, 'scope' => 'app'],
                        'sitemap_changefreq_category' => ['value' => 'daily', 'scope' => 'app'],
                        'sitemap_priority_category' => ['value' => 0.5, 'scope' => 'app'],
                        'sitemap_changefreq_cms_page' => ['value' => 'daily', 'scope' => 'app'],
                        'sitemap_priority_cms_page' => ['value' => 0.5, 'scope' => 'app'],
                        'sitemap_cron_definition' => ['value' => '0 0 * * *', 'scope' => 'app'],
                        'sitemap_exclude_landing_pages' => ['value' => true, 'scope' => 'app'],
                        'sitemap_include_landing_pages_not_in_web_catalog' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_seo')
        );
    }
}
