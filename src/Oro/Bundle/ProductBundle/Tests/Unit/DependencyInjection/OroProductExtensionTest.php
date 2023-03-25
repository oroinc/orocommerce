<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ProductBundle\DependencyInjection\OroProductExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroProductExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroProductExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'related_products_enabled' => ['value' => true, 'scope' => 'app'],
                        'related_products_bidirectional' => ['value' => false, 'scope' => 'app'],
                        'max_number_of_related_products' => ['value' => 25, 'scope' => 'app'],
                        'upsell_products_enabled' => ['value' => true, 'scope' => 'app'],
                        'max_number_of_upsell_products' => ['value' => 25, 'scope' => 'app'],
                        'related_products_max_items' => ['value' => 4, 'scope' => 'app'],
                        'related_products_min_items' => ['value' => 3, 'scope' => 'app'],
                        'related_products_show_add_button' => ['value' => true, 'scope' => 'app'],
                        'related_products_use_slider_on_mobile' => ['value' => false, 'scope' => 'app'],
                        'upsell_products_max_items' => ['value' => 4, 'scope' => 'app'],
                        'upsell_products_min_items' => ['value' => 3, 'scope' => 'app'],
                        'upsell_products_show_add_button' => ['value' => true, 'scope' => 'app'],
                        'upsell_products_use_slider_on_mobile' => ['value' => false, 'scope' => 'app'],
                        'unit_rounding_type' => ['value' => 6, 'scope' => 'app'],
                        'single_unit_mode' => ['value' => false, 'scope' => 'app'],
                        'single_unit_mode_show_code' => ['value' => false, 'scope' => 'app'],
                        'default_unit' => ['value' => 'each', 'scope' => 'app'],
                        'default_unit_precision' => ['value' => 0, 'scope' => 'app'],
                        'general_frontend_product_visibility' => [
                            'value' => ['in_stock', 'out_of_stock'],
                            'scope' => 'app'
                        ],
                        'product_image_watermark_file' => ['value' => null, 'scope' => 'app'],
                        'product_image_watermark_size' => ['value' => 100, 'scope' => 'app'],
                        'product_image_watermark_position' => ['value' => 'center', 'scope' => 'app'],
                        'product_image_placeholder' => ['value' => null, 'scope' => 'app'],
                        'featured_products_segment_id' => [
                            'value' => '@oro_product.provider.default_value.featured_products',
                            'scope' => 'app'
                        ],
                        'enable_quick_order_form' => ['value' => true, 'scope' => 'app'],
                        'product_direct_url_prefix' => ['value' => '', 'scope' => 'app'],
                        'product_collections_indexation_cron_schedule' => ['value' => '0 * * * *', 'scope' => 'app'],
                        'product_collections_indexation_partial' => ['value' => true, 'scope' => 'app'],
                        'product_promotion_show_on_product_view' => ['value' => false, 'scope' => 'app'],
                        'brand_direct_url_prefix' => ['value' => '', 'scope' => 'app'],
                        'product_collections_mass_action_limitation' => ['value' => 500, 'scope' => 'app'],
                        'new_arrivals_products_segment_id' => [
                            'value' => '@oro_product.provider.default_value.new_arrivals',
                            'scope' => 'app'
                        ],
                        'new_arrivals_max_items' => ['value' => 4, 'scope' => 'app'],
                        'new_arrivals_min_items' => ['value' => 3, 'scope' => 'app'],
                        'new_arrivals_use_slider_on_mobile' => ['value' => false, 'scope' => 'app'],
                        'image_preview_on_product_listing_enabled' => ['value' => true, 'scope' => 'app'],
                        'popup_gallery_on_product_view' => ['value' => true, 'scope' => 'app'],
                        'guest_quick_order_form' => ['value' => false, 'scope' => 'app'],
                        'matrix_form_on_product_view' => ['value' => 'inline', 'scope' => 'app'],
                        'matrix_form_on_product_listing' => ['value' => 'inline', 'scope' => 'app'],
                        'matrix_form_allow_empty' => ['value' => true, 'scope' => 'app'],
                        'display_simple_variations' => ['value' => 'hide_completely', 'scope' => 'app'],
                        'limit_filters_sorters_on_product_listing' => ['value' => true, 'scope' => 'app'],
                        'disable_filters_on_product_listing' => ['value' => true, 'scope' => 'app'],
                        'filters_display_settings_state' => ['value' => 'collapsed', 'scope' => 'app'],
                        'original_file_names_enabled' => ['value' => false, 'scope' => 'app'],
                        'search_autocomplete_max_products' => ['value' => 4, 'scope' => 'app'],
                        'filters_position' => ['value' => 'top', 'scope' => 'app'],
                        'allow_partial_product_search' => ['value' => false, 'scope' => 'app'],
                        'product_data_export_enabled' => ['value' => false, 'scope' => 'app'],
                        'product_prices_export_enabled' => ['value' => false, 'scope' => 'app'],
                        'product_price_tiers_export_enabled' => ['value' => false, 'scope' => 'app'],
                        'microdata_without_prices_disabled' => ['value' => true, 'scope' => 'app'],
                        'schema_org_description_field' => ['value' => 'oro_product_full_description', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_product')
        );

        self::assertEquals(
            Product::getTypes(),
            $container->getDefinition('oro_product.provider.product_type_provider')
                ->getArgument('$availableProductTypes')
        );
    }

    public function testLoadWithCustomProductTypesConfigs(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $configs = [
            ['product_types' => ['simple']],
            ['product_types' => ['configurable']],
        ];

        $extension = new OroProductExtension();
        $extension->load($configs, $container);

        self::assertEquals(
            ['simple', 'configurable'],
            $container->getDefinition('oro_product.provider.product_type_provider')
                ->getArgument('$availableProductTypes')
        );
    }
}
