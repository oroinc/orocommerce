<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWebCatalogExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroWebCatalogExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'web_catalog' => ['value' => null, 'scope' => 'app'],
                        'navigation_root' => ['value' => null, 'scope' => 'app'],
                        'enable_web_catalog_canonical_url' => ['value' => true, 'scope' => 'app'],
                        'empty_search_result_page' => ['value' => null, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_web_catalog')
        );
    }
}
