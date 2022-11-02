<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const MERCHANT_COUNTRY_KEY = 'merchant_country';

    const ALLOWED_COUNTRIES_ALL = 'all';
    const ALLOWED_COUNTRIES_SELECTED = 'selected';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
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
