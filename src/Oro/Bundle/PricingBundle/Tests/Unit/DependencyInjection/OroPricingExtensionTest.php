<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPricingExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroPricingExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'default_price_lists' => ['value' => [], 'scope' => 'app'],
                        'default_price_list' => ['value' => null, 'scope' => 'app'],
                        'price_storage' => ['value' => 'combined', 'scope' => 'app'],
                        'price_indexation_accuracy' => ['value' => 'customer', 'scope' => 'app'],
                        'rounding_type' => ['value' => RoundingServiceInterface::ROUND_HALF_UP, 'scope' => 'app'],
                        'precision' => ['value' => 2, 'scope' => 'app'],
                        'combined_price_list' => ['value' => null, 'scope' => 'app'],
                        'full_combined_price_list' => ['value' => null, 'scope' => 'app'],
                        'offset_of_processing_cpl_prices' => ['value' => 12.0, 'scope' => 'app'],
                        'price_strategy' => ['value' => MinimalPricesCombiningStrategy::NAME, 'scope' => 'app'],
                        'feature_enabled' => ['value' => true, 'scope' => 'app'],
                        'price_calculation_precision' => ['value' => null, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_pricing')
        );
    }
}
