<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTaxExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroTaxExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'tax_enable' => ['value' => true, 'scope' => 'app'],
                        'tax_provider' => ['value' => 'built_in', 'scope' => 'app'],
                        'start_calculation_with' => ['value' => 'unit_price', 'scope' => 'app'],
                        'start_calculation_on' => ['value' => 'total', 'scope' => 'app'],
                        'product_prices_include_tax' => ['value' => false, 'scope' => 'app'],
                        'use_as_base_by_default' => ['value' => 'origin', 'scope' => 'app'],
                        'use_as_base_exclusions' => ['value' => [], 'scope' => 'app'],
                        'destination' => ['value' => 'shipping_address', 'scope' => 'app'],
                        'digital_products_us' => ['value' => [], 'scope' => 'app'],
                        'digital_products_eu' => ['value' => [], 'scope' => 'app'],
                        'origin_address' => ['value' => [], 'scope' => 'app'],
                        'shipping_tax_code' => ['value' => [], 'scope' => 'app'],
                        'shipping_rates_include_tax' => ['value' => false, 'scope' => 'app'],
                        'address_resolver_granularity' => ['value' =>  'zip_code', 'scope' => 'app'],
                        'calculate_taxes_after_promotions' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_tax')
        );
    }
}
