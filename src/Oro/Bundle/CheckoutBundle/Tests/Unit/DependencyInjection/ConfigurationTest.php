<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyConfiguration(): void
    {
        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'frontend_open_orders_separate_page' => ['value' => false, 'scope' => 'app'],
                    'guest_checkout' => ['value' => false, 'scope' => 'app'],
                    'single_page_checkout_increase_performance' => ['value' => false, 'scope' => 'app'],
                    'registration_allowed' => ['value' => true, 'scope' => 'app'],
                    'default_guest_checkout_owner' => ['value' => null, 'scope' => 'app'],
                    'allow_checkout_without_email_confirmation' => ['value' => false, 'scope' => 'app'],
                    'frontend_show_open_orders' => ['value' => true, 'scope' => 'app'],
                    'checkout_max_line_items_per_page' => ['value' => 1000, 'scope' => 'app'],
                    'enable_line_item_grouping' => ['value' => false, 'scope' => 'app'],
                    'group_line_items_by' => ['value' => 'product.category', 'scope' => 'app'],
                    'create_suborders_for_each_group' => ['value' => false, 'scope' => 'app'],
                    'enable_shipping_method_selection_per_line_item' => ['value' => false, 'scope' => 'app'],
                    'show_suborders_in_order_history' => ['value' => true, 'scope' => 'app'],
                    'show_main_orders_in_order_history' => ['value' => true, 'scope' => 'app'],
                ]
            ],
            (new Processor())->processConfiguration(new Configuration(), [])
        );
    }
}
