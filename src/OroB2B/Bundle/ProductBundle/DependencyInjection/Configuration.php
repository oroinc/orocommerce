<?php

namespace OroB2B\Bundle\ProductBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('orob2b_product');

        SettingsBuilder::append(
            $rootNode,
            [
                'unit_rounding_type' => ['value' => RoundingService::HALF_UP],
                'default_visibility' => ['value' => Product::VISIBILITY_VISIBLE],
            ]
        );

        return $treeBuilder;
    }
}
