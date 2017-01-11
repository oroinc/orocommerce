<?php

namespace Oro\Bundle\MoneyOrderBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const MONEY_ORDER_LABEL_KEY = 'money_order_label';
    const MONEY_ORDER_SHORT_LABEL_KEY = 'money_order_short_label';
    const MONEY_ORDER_PAY_TO_KEY = 'money_order_pay_to';
    const MONEY_ORDER_SEND_TO_KEY = 'money_order_send_to';

    const MONEY_ORDER_LABEL = 'Check/Money Order';
    const MONEY_ORDER_SORT_ORDER = 50;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroMoneyOrderExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::MONEY_ORDER_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::MONEY_ORDER_LABEL
                ],
                self::MONEY_ORDER_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::MONEY_ORDER_LABEL
                ],
                self::MONEY_ORDER_PAY_TO_KEY => [
                    'type' => 'string',
                    'value' => ''
                ],
                self::MONEY_ORDER_SEND_TO_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
            ]
        );

        return $treeBuilder;
    }
}
