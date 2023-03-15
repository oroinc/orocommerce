<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPayPalExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroPayPalExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertSame([], $container->getParameter('oro_paypal.allowed_ips'));
    }

    public function testLoadWithConfiguresAllowedIps(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $configs = [
            ['allowed_ips' => ['255.255.255.1']]
        ];
        $extension = new OroPayPalExtension();
        $extension->load($configs, $container);

        self::assertEquals(['255.255.255.1'], $container->getParameter('oro_paypal.allowed_ips'));
    }

    public function testGetAlias()
    {
        self::assertEquals('oro_paypal', (new OroPayPalExtension())->getAlias());
    }
}
