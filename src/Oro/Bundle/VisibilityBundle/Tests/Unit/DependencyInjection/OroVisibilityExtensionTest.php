<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\VisibilityBundle\DependencyInjection\OroVisibilityExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroVisibilityExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroVisibilityExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'category_visibility' => ['value' => 'visible', 'scope' => 'app'],
                        'product_visibility' => ['value' => 'visible', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_visibility')
        );
    }

    public function testPrepend(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('security', [
            [
                'firewalls' => [
                    'frontend' => ['frontend_config'],
                    'frontend_secure' => ['frontend_secure_config'],
                    'main' => ['main_config'],
                ]
            ]
        ]);

        $extension = new OroVisibilityExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'firewalls' => [
                        'main' => ['main_config'],
                        'frontend_secure' => ['frontend_secure_config'],
                        'frontend' => ['frontend_config'],
                    ]
                ]
            ],
            $container->getExtensionConfig('security')
        );
    }
}
