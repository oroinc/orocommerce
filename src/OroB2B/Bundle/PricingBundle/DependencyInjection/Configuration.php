<?php

namespace OroB2B\Bundle\PricingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('orob2b_pricing');

        SettingsBuilder::append(
            $rootNode,
            [
                'rounding_type' => ['value' => PriceRoundingService::HALF_UP],
                'precision' => ['value' => PriceRoundingService::FALLBACK_PRECISION],
            ]
        );

        return $treeBuilder;
    }
}
