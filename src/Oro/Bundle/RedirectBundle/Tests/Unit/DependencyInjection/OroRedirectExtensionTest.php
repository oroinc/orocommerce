<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRedirectExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroRedirectExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'enable_direct_url' => ['value' => true, 'scope' => 'app'],
                        'canonical_url_type' => ['value' => 'system', 'scope' => 'app'],
                        'redirect_generation_strategy' => ['value' => 'ask', 'scope' => 'app'],
                        'canonical_url_security_type' => ['value' => 'secure', 'scope' => 'app'],
                        'use_localized_canonical' => ['value' => true, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_redirect')
        );
    }
}
