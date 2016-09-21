<?php

namespace Oro\Bundle\VisibilityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroAccountExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'category_visibility' => ['value' => CategoryVisibility::VISIBLE],
                'product_visibility' => ['value' => ProductVisibility::VISIBLE],
            ]
        );

        return $treeBuilder;
    }
}
