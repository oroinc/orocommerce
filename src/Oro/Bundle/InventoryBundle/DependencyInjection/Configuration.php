<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const MINIMUM_QUANTITY_TO_ORDER = 'minimum_quantity_to_order';
    const MAXIMUM_QUANTITY_TO_ORDER = 'maximum_quantity_to_order';
    const DEFAULT_MAXIMUM_QUANTITY_TO_ORDER = 100000;

    /**
     * @return string
     */
    public static function getMaximumQuantityToOrderFullConfigurationName()
    {
        return OroInventoryExtension::ALIAS . '.' . self::MAXIMUM_QUANTITY_TO_ORDER;
    }

    /**
     * @return string
     */
    public static function getMinimumQuantityToOrderFullConfigurationName()
    {
        return OroInventoryExtension::ALIAS . '.' . self::MINIMUM_QUANTITY_TO_ORDER;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroInventoryExtension::ALIAS);
        SettingsBuilder::append(
            $rootNode,
            [
                self::MINIMUM_QUANTITY_TO_ORDER => ['type' => 'decimal', 'value' => null],
                self::MAXIMUM_QUANTITY_TO_ORDER => [
                    'type' => 'decimal',
                    'value' => self::DEFAULT_MAXIMUM_QUANTITY_TO_ORDER,
                ],
            ]
        );

        return $treeBuilder;
    }
}
