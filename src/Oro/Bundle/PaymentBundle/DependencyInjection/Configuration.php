<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const MERCHANT_COUNTRY_KEY = 'merchant_country';

    public const ALLOWED_COUNTRIES_ALL = 'all';
    public const ALLOWED_COUNTRIES_SELECTED = 'selected';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_payment');

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                // General
                self::MERCHANT_COUNTRY_KEY => [
                    'type' => 'text',
                    'value' => LocaleConfiguration::DEFAULT_COUNTRY,
                ],
            ]
        );

        return $treeBuilder;
    }
}
