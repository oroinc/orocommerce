<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ConsentBundle\DependencyInjection\OroConsentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroConsentExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroConsentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'consent_feature_enabled' => ['value' => false, 'scope' => 'app'],
                        'enabled_consents' => ['value' => [], 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_consent')
        );
    }
}
