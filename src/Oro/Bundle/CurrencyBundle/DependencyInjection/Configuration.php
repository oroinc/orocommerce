<?php

namespace Oro\Bundle\CurrencyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;

class Configuration implements ConfigurationInterface
{
    /**
     * @var array
     */
    public static $defaultCurrencies = [LocaleConfiguration::DEFAULT_CURRENCY];

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_currency');

        SettingsBuilder::append(
            $rootNode,
            [
                'allowed_currencies' => ['value' => self::$defaultCurrencies, 'type' => 'array'],
            ]
        );

        return $treeBuilder;
    }
}
