<?php

namespace Oro\Bundle\InfinitePayBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const INFINITEPAY_ENABLED_KEY = 'infinite_pay_enabled';
    const INFINITEPAY_SORT_ORDER_KEY = 'infinite_pay_sort_order';
    const INFINITEPAY_LABEL_KEY = 'infinite_pay_label';
    const INFINITEPAY_LABEL_SHORT_KEY = 'infinite_pay_label_short';
    const INFINITEPAY_AUTO_CAPTURE_KEY = 'infinite_pay_auto_capture';
    const INFINITEPAY_AUTO_ACTIVATE_KEY = 'infinite_pay_auto_activate';

    const LABEL_INFINITE_PAY_INVOICE = 'Infinite Pay (Invoice)';
    const INFINITEPAY_API_DEBUG_MODE_KEY = 'infinite_pay_debug_mode';
    const INFINITEPAY_CLIENT_REF_KEY = 'infinite_pay_client_ref';
    const INFINITEPAY_USERNAME_TOKEN_KEY = 'infinite_pay_username_token';
    const INFINITEPAY_USERNAME_KEY = 'infinite_pay_username';
    const INFINITEPAY_PASSWORD_KEY = 'infinite_pay_password';
    const INFINITPAY_SECRET_KEY = 'infinite_pay_secret';

    const INFINITYPAY_INVOICE_DUE_PERIOD = 'invoice_due_period';
    const INFINITYPAY_INVOICE_SHIPPING_DURATION = 'invoice_shipping_duration';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroInfinitePayExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                static::INFINITEPAY_ENABLED_KEY => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                static::INFINITEPAY_SORT_ORDER_KEY => [
                    'type' => 'string',
                    'value' => 30,
                ],
                static::INFINITEPAY_LABEL_KEY => [
                    'type' => 'string',
                    'value' => static::LABEL_INFINITE_PAY_INVOICE,
                ],
                static::INFINITEPAY_LABEL_SHORT_KEY => [
                    'type' => 'string',
                    'value' => static::LABEL_INFINITE_PAY_INVOICE,
                ],
                static::INFINITEPAY_AUTO_CAPTURE_KEY => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                static::INFINITEPAY_AUTO_ACTIVATE_KEY => [
                    'type' => 'string',
                    'value' => false,
                ],
                static::INFINITEPAY_API_DEBUG_MODE_KEY => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                static::INFINITEPAY_CLIENT_REF_KEY => [
                    'type' => 'string',
                    'value' => '',
                ],
                static::INFINITEPAY_USERNAME_TOKEN_KEY => [
                    'type' => 'string',
                    'value' => '',
                ],
                static::INFINITEPAY_USERNAME_KEY => [
                    'type' => 'string',
                    'value' => '',
                ],
                static::INFINITEPAY_PASSWORD_KEY => [
                    'type' => 'string',
                    'value' => '',
                ],
                static::INFINITPAY_SECRET_KEY => [
                    'type' => 'string',
                    'value' => '',
                ],
                static::INFINITYPAY_INVOICE_DUE_PERIOD => [
                    'type' => 'integer',
                    'value' => 30,
                ],
                static::INFINITYPAY_INVOICE_SHIPPING_DURATION => [
                    'type' => 'integer',
                    'value' => 21,
                ],
            ]
        );

        return $treeBuilder;
    }
}
