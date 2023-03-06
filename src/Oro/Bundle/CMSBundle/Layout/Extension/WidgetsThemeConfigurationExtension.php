<?php

namespace Oro\Bundle\CMSBundle\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "widgets" section to the layout theme configuration.
 *
 * The widgets configuration can be loaded from the following files:
 * * Resources/views/layouts/{folder}/theme.yml
 * * Resources/views/layouts/{folder}/config/widgets.yml
 */
class WidgetsThemeConfigurationExtension implements ThemeConfigurationExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigFileNames(): array
    {
        return ['widgets.yml'];
    }

    /**
     * {@inheritdoc}
     */
    public function appendConfig(NodeBuilder $configNode): void
    {
        $configNode->arrayNode('widgets')
            ->children()
                ->arrayNode('layouts')
                    ->prototype('array')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
