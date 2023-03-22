<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CatalogBundle\DependencyInjection\OroCatalogExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCatalogExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroCatalogExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'category_direct_url_prefix' => ['value' => '', 'scope' => 'app'],
                        'all_products_page_enabled' => ['value' => false, 'scope' => 'app'],
                        'category_image_placeholder' => ['value' => null, 'scope' => 'app'],
                        'search_autocomplete_max_categories' => ['value' => 2, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_catalog')
        );
    }
}
