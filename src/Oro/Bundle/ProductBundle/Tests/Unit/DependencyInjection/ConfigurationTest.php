<?php
declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }

    public function testGetConfigKeyByName()
    {
        $key = 'options';
        $configKey = Configuration::getConfigKeyByName($key);
        static::assertEquals('oro_product.'.$key, $configKey);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $expected = [
            'settings' => [
                'resolved' => true,
                'related_products_enabled' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'related_products_bidirectional' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'max_number_of_related_products' => [
                    'value' => 25,
                    'scope' => 'app'
                ],
                'upsell_products_enabled' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'max_number_of_upsell_products' => [
                    'value' => 25,
                    'scope' => 'app'
                ],
                'related_products_max_items' => [
                    'value' => 4,
                    'scope' => 'app'
                ],
                'related_products_min_items' => [
                    'value' => 3,
                    'scope' => 'app'
                ],
                'related_products_show_add_button' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'related_products_use_slider_on_mobile' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'upsell_products_max_items' => [
                    'value' => 4,
                    'scope' => 'app'
                ],
                'upsell_products_min_items' => [
                    'value' => 3,
                    'scope' => 'app'
                ],
                'upsell_products_show_add_button' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'upsell_products_use_slider_on_mobile' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'unit_rounding_type' => [
                    'value' => RoundingServiceInterface::ROUND_HALF_UP,
                    'scope' => 'app'
                ],
                'single_unit_mode' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'single_unit_mode_show_code' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'default_unit' => [
                    'value' => 'each',
                    'scope' => 'app'
                ],
                'default_unit_precision' => [
                    'value' => 0,
                    'scope' => 'app'
                ],
                'general_frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ],
                    'scope' => 'app'
                ],
                'product_image_watermark_file' => [
                    'value' => null,
                    'scope' => 'app'
                ],
                'product_image_watermark_size' => [
                    'value' => 100,
                    'scope' => 'app'
                ],
                'product_image_watermark_position' => [
                    'value' => 'center',
                    'scope' => 'app'
                ],
                'enable_quick_order_form' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'product_direct_url_prefix' => [
                    'value' => '',
                    'scope' => 'app'
                ],
                'brand_direct_url_prefix' => [
                    'value' => '',
                    'scope' => 'app'
                ],
                'featured_products_segment_id' => [
                    'value' => '@oro_product.provider.default_value.featured_products',
                    'scope' => 'app'
                ],
                'product_collections_indexation_cron_schedule' => [
                    'value' => '0 * * * *',
                    'scope' => 'app'
                ],
                'product_collections_indexation_partial' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'product_promotion_show_on_product_view' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'product_collections_mass_action_limitation' => [
                    'value' => 500,
                    'scope' => 'app'
                ],
                'new_arrivals_products_segment_id' => [
                    'value' => '@oro_product.provider.default_value.new_arrivals',
                    'scope' => 'app',
                ],
                'new_arrivals_max_items' => [
                    'value' => 4,
                    'scope' => 'app',
                ],
                'new_arrivals_min_items' => [
                    'value' => 3,
                    'scope' => 'app',
                ],
                'new_arrivals_use_slider_on_mobile' => [
                    'value' => false,
                    'scope' => 'app',
                ],
                'image_preview_on_product_listing_enabled' => [
                    'value' => true,
                    'scope' => 'app',
                ],
                'popup_gallery_on_product_view' => [
                    'value' => true,
                    'scope' => 'app',
                ],
                'guest_quick_order_form' => [
                    'value' => false,
                    'scope' => 'app',
                ],
                'matrix_form_on_product_view' => [
                    'value' => 'inline',
                    'scope' => 'app',
                ],
                'matrix_form_on_product_listing' => [
                    'value' => 'inline',
                    'scope' => 'app',
                ],
                'matrix_form_allow_empty' => [
                    'value' => true,
                    'scope' => 'app',
                ],
                Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY_BC => [
                    'value' => false,
                    'scope' => 'app',
                ],
                Configuration::DISPLAY_SIMPLE_VARIATIONS => [
                    'value' => Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY,
                    'scope' => 'app',
                ],
                Configuration::LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING => [
                    'value' => true,
                    'scope' => 'app'
                ],
                Configuration::DISABLE_FILTERS_ON_PRODUCT_LISTING => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'product_image_placeholder' => [
                    'value' => null,
                    'scope' => 'app',
                ],
                'filters_display_settings_state' => [
                    'value' => 'collapsed',
                    'scope' => 'app'
                ],
                'original_file_names_enabled' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'search_autocomplete_max_products' => [
                    'value' => 4,
                    'scope' => 'app'
                ],
                'filters_position' => [
                    'value' => 'top',
                    'scope' => 'app'
                ],
                'allow_partial_product_search' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'product_data_export_enabled' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'product_prices_export_enabled' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'product_price_tiers_export_enabled' => [
                    'value' => false,
                    'scope' => 'app'
                ]
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
