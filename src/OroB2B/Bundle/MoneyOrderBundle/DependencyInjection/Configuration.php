<?php

namespace OroB2B\Bundle\MoneyOrderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;

class Configuration implements ConfigurationInterface
{
    const MONEY_ORDER_ENABLED_KEY = 'money_order_enabled';
    const MONEY_ORDER_LABEL_KEY = 'money_order_label';
    const MONEY_ORDER_SORT_ORDER_KEY = 'money_order_sort_order';
    const MONEY_ORDER_PAY_TO_KEY = 'money_order_pay_to';
    const MONEY_ORDER_SEND_TO_KEY = 'money_order_send_to';
    const MONEY_ORDER_ALLOWED_COUNTRIES_KEY = 'money_order_allowed_countries';
    const MONEY_ORDER_SELECTED_COUNTRIES_KEY = 'money_order_selected_countries';

    const MONEY_ORDER_LABEL = 'Check/Money Order';
    const MONEY_ORDER_SORT_ORDER = 40;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BMoneyOrderExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::MONEY_ORDER_ENABLED_KEY           => [
                    'type'  => 'boolean',
                    'value' => false
                ],
                self::MONEY_ORDER_LABEL_KEY             => [
                    'type'  => 'text',
                    'value' => self::MONEY_ORDER_LABEL
                ],
                self::MONEY_ORDER_SORT_ORDER_KEY        => [
                    'type'  => 'string',
                    'value' => self::MONEY_ORDER_SORT_ORDER
                ],
                self::MONEY_ORDER_PAY_TO_KEY            => [
                    'type'  => 'string',
                    'value' => ''
                ],
                self::MONEY_ORDER_SEND_TO_KEY           => [
                    'type'  => 'text',
                    'value' => ''
                ],
                self::MONEY_ORDER_ALLOWED_COUNTRIES_KEY => [
                    'type'  => 'text',
                    'value' => PaymentConfiguration::ALLOWED_COUNTRIES_ALL
                ],
                self::MONEY_ORDER_SELECTED_COUNTRIES_KEY         => [
                    'type'  => 'array',
                    'value' => []
                ],
            ]
        );

        return $treeBuilder;
    }
}
