<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FixedProductShippingBundle\DependencyInjection\OroFixedProductShippingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFixedProductShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroFixedProductShippingExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
