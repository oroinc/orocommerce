<?php

namespace OroB2B\Bundle\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;

class Configuration implements ConfigurationInterface
{
    const MERCHANT_COUNTRY_KEY = 'merchant_country';

    const PAYMENT_TERM_ENABLED_KEY = 'payment_term_enabled';
    const PAYMENT_TERM_LABEL_KEY = 'payment_term_label';
    const PAYMENT_TERM_SHORT_LABEL_KEY = 'payment_term_short_label';
    const PAYMENT_TERM_SORT_ORDER_KEY = 'payment_term_sort_order';
    const PAYMENT_TERM_ALLOWED_COUNTRIES_KEY = 'payment_term_allowed_countries';
    const PAYMENT_TERM_SELECTED_COUNTRIES_KEY = 'payment_term_selected_countries';
    const PAYMENT_TERM_ALLOWED_CURRENCIES = 'payment_term_allowed_currencies';

    const ALLOWED_COUNTRIES_ALL = 'all';
    const ALLOWED_COUNTRIES_SELECTED = 'selected';

    const PAYMENT_TERM_LABEL = 'Payment Terms';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BPaymentExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                // General
                self::MERCHANT_COUNTRY_KEY => [
                    'type' => 'text',
                    'value' => '',
                ],

                // Payment Term
                self::PAYMENT_TERM_ENABLED_KEY => [
                    'type' => 'boolean',
                    'value' => true
                ],
                self::PAYMENT_TERM_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYMENT_TERM_LABEL
                ],
                self::PAYMENT_TERM_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYMENT_TERM_LABEL
                ],
                self::PAYMENT_TERM_SORT_ORDER_KEY => [
                    'type' => 'string',
                    'value' => '50'
                ],
                self::PAYMENT_TERM_ALLOWED_COUNTRIES_KEY => [
                    'type' => 'text',
                    'value' => self::ALLOWED_COUNTRIES_ALL
                ],
                self::PAYMENT_TERM_SELECTED_COUNTRIES_KEY => [
                    'type' => 'array',
                    'value' => []
                ],
                self::PAYMENT_TERM_ALLOWED_CURRENCIES => [
                    'type' => 'array',
                    'value' => CurrencyConfiguration::$defaultCurrencies,
                ],
            ]
        );

        return $treeBuilder;
    }

    /**
     * @param $key
     * @return string
     */
    public static function getFullConfigKey($key)
    {
        return OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
