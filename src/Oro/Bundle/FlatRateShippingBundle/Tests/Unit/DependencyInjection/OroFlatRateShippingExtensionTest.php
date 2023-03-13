<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FlatRateShippingBundle\DependencyInjection\OroFlatRateShippingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFlatRateShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroFlatRateShippingExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
