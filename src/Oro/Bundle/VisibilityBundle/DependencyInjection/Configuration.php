<?php

namespace Oro\Bundle\VisibilityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroVisibilityExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'category_visibility' => ['value' => CategoryVisibility::VISIBLE],
                ProductVisibility::VISIBILITY_TYPE => ['value' => ProductVisibility::VISIBLE],
            ]
        );

        return $treeBuilder;
    }
}
