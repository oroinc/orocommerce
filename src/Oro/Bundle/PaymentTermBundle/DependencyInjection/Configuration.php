<?php

namespace Oro\Bundle\PaymentTermBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;

class Configuration implements ConfigurationInterface
{
    const PAYMENT_TERM_ENABLED_KEY = 'payment_term_enabled';
    const PAYMENT_TERM_LABEL_KEY = 'payment_term_label';
    const PAYMENT_TERM_SHORT_LABEL_KEY = 'payment_term_short_label';
    const PAYMENT_TERM_SORT_ORDER_KEY = 'payment_term_sort_order';
    const PAYMENT_TERM_ALLOWED_COUNTRIES_KEY = 'payment_term_allowed_countries';
    const PAYMENT_TERM_SELECTED_COUNTRIES_KEY = 'payment_term_selected_countries';
    const PAYMENT_TERM_ALLOWED_CURRENCIES = 'payment_term_allowed_currencies';

    const PAYMENT_TERM_LABEL = 'Payment Terms';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroPaymentTermExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                // Payment Term
                self::PAYMENT_TERM_ENABLED_KEY => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                self::PAYMENT_TERM_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYMENT_TERM_LABEL,
                ],
                self::PAYMENT_TERM_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYMENT_TERM_LABEL,
                ],
                self::PAYMENT_TERM_SORT_ORDER_KEY => [
                    'type' => 'string',
                    'value' => '50',
                ],
                self::PAYMENT_TERM_ALLOWED_COUNTRIES_KEY => [
                    'type' => 'text',
                    'value' => PaymentConfiguration::ALLOWED_COUNTRIES_ALL,
                ],
                self::PAYMENT_TERM_SELECTED_COUNTRIES_KEY => [
                    'type' => 'array',
                    'value' => [],
                ],
                self::PAYMENT_TERM_ALLOWED_CURRENCIES => [
                    'type' => 'array',
                    'value' => CurrencyConfiguration::$defaultCurrencies,
                ],
            ]
        );

        return $treeBuilder;
    }
}
