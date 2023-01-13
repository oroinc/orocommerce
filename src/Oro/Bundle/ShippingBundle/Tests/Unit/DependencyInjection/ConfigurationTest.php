<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShippingBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfiguration(): void
    {
        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'shipping_origin' => ['value' => [], 'scope' => 'app'],
                    'length_units' => ['value' => ['inch', 'foot', 'cm', 'm'], 'scope' => 'app'],
                    'weight_units' => ['value' => ['lbs', 'kg'], 'scope' => 'app'],
                    'freight_classes' => ['value' => ['parcel'], 'scope' => 'app'],
                ]
            ],
            (new Processor())->processConfiguration(new Configuration(), [])
        );
    }
}
