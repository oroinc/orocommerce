<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroShippingExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'shipping_origin' => ['value' => [], 'scope' => 'app'],
                        'length_units' => ['value' => ['inch', 'foot', 'cm', 'm'], 'scope' => 'app'],
                        'weight_units' => ['value' => ['lbs', 'kg'], 'scope' => 'app'],
                        'freight_classes' => ['value' => ['parcel'], 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_shipping')
        );
    }
}
