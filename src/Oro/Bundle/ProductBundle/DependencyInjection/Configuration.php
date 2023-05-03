<?php
declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_product';
    const RELATED_PRODUCTS_ENABLED = 'related_products_enabled';
    const RELATED_PRODUCTS_BIDIRECTIONAL = 'related_products_bidirectional';
    const MAX_NUMBER_OF_RELATED_PRODUCTS = 'max_number_of_related_products';
    const MAX_NUMBER_OF_RELATED_PRODUCTS_COUNT = 25;
    const UPSELL_PRODUCTS_ENABLED = 'upsell_products_enabled';
    const MAX_NUMBER_OF_UPSELL_PRODUCTS = 'max_number_of_upsell_products';
    const MAX_NUMBER_OF_UPSELL_PRODUCTS_COUNT = 25;
    const RELATED_PRODUCTS_MAX_ITEMS = 'related_products_max_items';
    const RELATED_PRODUCTS_MAX_ITEMS_COUNT = 4;
    const RELATED_PRODUCTS_MIN_ITEMS = 'related_products_min_items';
    const RELATED_PRODUCTS_MIN_ITEMS_COUNT = 3;
    const RELATED_PRODUCTS_SHOW_ADD_BUTTON = 'related_products_show_add_button';
    const RELATED_PRODUCTS_USE_SLIDER_ON_MOBILE = 'related_products_use_slider_on_mobile';
    const UPSELL_PRODUCTS_MAX_ITEMS = 'upsell_products_max_items';
    const UPSELL_PRODUCTS_MAX_ITEMS_COUNT = 4;
    const UPSELL_PRODUCTS_MIN_ITEMS = 'upsell_products_min_items';
    const UPSELL_PRODUCTS_MIN_ITEMS_COUNT = 3;
    const UPSELL_PRODUCTS_SHOW_ADD_BUTTON = 'upsell_products_show_add_button';
    const UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE = 'upsell_products_use_slider_on_mobile';
    const SINGLE_UNIT_MODE = 'single_unit_mode';
    const SINGLE_UNIT_MODE_SHOW_CODE = 'single_unit_mode_show_code';
    const DEFAULT_UNIT = 'default_unit';
    const PRODUCT_IMAGE_WATERMARK_FILE = 'product_image_watermark_file';
    const PRODUCT_IMAGE_WATERMARK_SIZE = 'product_image_watermark_size';
    const PRODUCT_IMAGE_WATERMARK_POSITION = 'product_image_watermark_position';
    const PRODUCT_IMAGE_PLACEHOLDER = 'product_image_placeholder';
    const FEATURED_PRODUCTS_SEGMENT_ID = 'featured_products_segment_id';
    const ENABLE_QUICK_ORDER_FORM = 'enable_quick_order_form';
    const GUEST_QUICK_ORDER_FORM = 'guest_quick_order_form';
    const DIRECT_URL_PREFIX = 'product_direct_url_prefix';
    const BRAND_DIRECT_URL_PREFIX = 'brand_direct_url_prefix';
    const PRODUCT_COLLECTIONS_INDEXATION_CRON_SCHEDULE = 'product_collections_indexation_cron_schedule';
    const PRODUCT_COLLECTIONS_INDEXATION_PARTIAL = 'product_collections_indexation_partial';
    const DEFAULT_CRON_SCHEDULE = '0 * * * *';
    const PRODUCT_PROMOTION_SHOW_ON_VIEW = 'product_promotion_show_on_product_view';
    const PRODUCT_COLLECTION_MASS_ACTION_LIMITATION = 'product_collections_mass_action_limitation';
    const NEW_ARRIVALS_PRODUCT_SEGMENT_ID = 'new_arrivals_products_segment_id';
    const NEW_ARRIVALS_MAX_ITEMS = 'new_arrivals_max_items';
    const NEW_ARRIVALS_MIN_ITEMS = 'new_arrivals_min_items';
    const NEW_ARRIVALS_USE_SLIDER_ON_MOBILE = 'new_arrivals_use_slider_on_mobile';
    const IMAGE_PREVIEW_ON_PRODUCT_LISTING_ENABLED = 'image_preview_on_product_listing_enabled';
    const POPUP_GALLERY_ON_PRODUCT_VIEW = 'popup_gallery_on_product_view';
    const MATRIX_FORM_ON_PRODUCT_VIEW = 'matrix_form_on_product_view';
    const MATRIX_FORM_ON_PRODUCT_LISTING = 'matrix_form_on_product_listing';
    const MATRIX_FORM_NONE = 'none';
    const MATRIX_FORM_INLINE = 'inline';
    const MATRIX_FORM_POPUP = 'popup';
    const MATRIX_FORM_ALLOW_TO_ADD_EMPTY = 'matrix_form_allow_empty';
    const DISPLAY_SIMPLE_VARIATIONS = 'display_simple_variations';
    const DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE = 'everywhere';
    const DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY = 'hide_completely';
    const DISPLAY_SIMPLE_VARIATIONS_HIDE_CATALOG = 'hide_catalog';
    const LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING = 'limit_filters_sorters_on_product_listing';
    const DISABLE_FILTERS_ON_PRODUCT_LISTING = 'disable_filters_on_product_listing';
    const FILTERS_DISPLAY_SETTINGS_STATE = 'filters_display_settings_state';
    const FILTERS_DISPLAY_SETTINGS_STATE_COLLAPSED = 'collapsed';
    const FILTERS_DISPLAY_SETTINGS_STATE_EXPANDED = 'expanded';
    const ORIGINAL_FILE_NAMES_ENABLED = 'original_file_names_enabled';
    const SEARCH_AUTOCOMPLETE_MAX_PRODUCTS = 'search_autocomplete_max_products';
    const FILTERS_POSITION = 'filters_position';
    const FILTERS_POSITION_TOP = 'top';
    const FILTERS_POSITION_SIDEBAR = 'sidebar';
    const ALLOW_PARTIAL_PRODUCT_SEARCH = 'allow_partial_product_search';
    const PRODUCT_DATA_EXPORT_ENABLED = 'product_data_export_enabled';
    const PRODUCT_PRICES_EXPORT_ENABLED = 'product_prices_export_enabled';
    const PRODUCT_PRICE_TIERS_ENABLED = 'product_price_tiers_export_enabled';
    const MICRODATA_WITHOUT_PRICES_DISABLED = 'microdata_without_prices_disabled';
    const SCHEMA_ORG_DESCRIPTION_FIELD = 'schema_org_description_field';
    const SCHEMA_ORG_DEFAULT_DESCRIPTION = 'oro_product_full_description';
    const PRODUCT_TYPES = 'product_types';

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(static::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                static::RELATED_PRODUCTS_ENABLED => ['value' => true],
                static::RELATED_PRODUCTS_BIDIRECTIONAL => ['value' => false],
                static::MAX_NUMBER_OF_RELATED_PRODUCTS => [
                    'value' => static::MAX_NUMBER_OF_RELATED_PRODUCTS_COUNT,
                ],
                static::UPSELL_PRODUCTS_ENABLED => ['value' => true],
                static::MAX_NUMBER_OF_UPSELL_PRODUCTS => [
                    'value' => static::MAX_NUMBER_OF_UPSELL_PRODUCTS_COUNT
                ],
                static::RELATED_PRODUCTS_MAX_ITEMS => [
                    'value' => static::RELATED_PRODUCTS_MAX_ITEMS_COUNT,
                ],
                static::RELATED_PRODUCTS_MIN_ITEMS => [
                    'value' => static::RELATED_PRODUCTS_MIN_ITEMS_COUNT,
                ],
                static::RELATED_PRODUCTS_SHOW_ADD_BUTTON => ['value' => true],
                static::RELATED_PRODUCTS_USE_SLIDER_ON_MOBILE => ['value' => false],
                static::UPSELL_PRODUCTS_MAX_ITEMS => [
                    'value' => static::UPSELL_PRODUCTS_MAX_ITEMS_COUNT,
                ],
                static::UPSELL_PRODUCTS_MIN_ITEMS => [
                    'value' => static::UPSELL_PRODUCTS_MIN_ITEMS_COUNT,
                ],
                static::UPSELL_PRODUCTS_SHOW_ADD_BUTTON => ['value' => true],
                static::UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE => ['value' => false],
                'unit_rounding_type' => ['value' => RoundingServiceInterface::ROUND_HALF_UP],
                static::SINGLE_UNIT_MODE => ['value' => false, 'type' => 'boolean'],
                static::SINGLE_UNIT_MODE_SHOW_CODE => ['value' => false, 'type' => 'boolean'],
                static::DEFAULT_UNIT => ['value' => 'each'],
                'default_unit_precision' => ['value' => 0],
                'general_frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                static::PRODUCT_IMAGE_WATERMARK_FILE => ['value' => null],
                static::PRODUCT_IMAGE_WATERMARK_SIZE => ['value' => 100],
                static::PRODUCT_IMAGE_WATERMARK_POSITION => ['value' => 'center'],
                static::PRODUCT_IMAGE_PLACEHOLDER => ['value' => null],
                static::FEATURED_PRODUCTS_SEGMENT_ID => [
                    'value' => '@oro_product.provider.default_value.featured_products'
                ],
                static::ENABLE_QUICK_ORDER_FORM => ['type' => 'boolean', 'value' => true],
                static::DIRECT_URL_PREFIX => ['value' => ''],
                static::PRODUCT_COLLECTIONS_INDEXATION_CRON_SCHEDULE => ['value' => static::DEFAULT_CRON_SCHEDULE],
                static::PRODUCT_COLLECTIONS_INDEXATION_PARTIAL => ['value' => true, 'type' => 'boolean'],
                static::PRODUCT_PROMOTION_SHOW_ON_VIEW => ['value' => false, 'type' => 'boolean'],
                static::BRAND_DIRECT_URL_PREFIX => ['value' => ''],
                static::PRODUCT_COLLECTION_MASS_ACTION_LIMITATION => ['value' => 500],
                static::NEW_ARRIVALS_PRODUCT_SEGMENT_ID => [
                    'value' => '@oro_product.provider.default_value.new_arrivals'
                ],
                static::NEW_ARRIVALS_MAX_ITEMS => ['type' => 'integer', 'value' => 4],
                static::NEW_ARRIVALS_MIN_ITEMS => ['type' => 'integer', 'value' => 3],
                static::NEW_ARRIVALS_USE_SLIDER_ON_MOBILE => ['type' => 'boolean', 'value' => false],
                static::IMAGE_PREVIEW_ON_PRODUCT_LISTING_ENABLED => ['type' => 'boolean', 'value' => true],
                static::POPUP_GALLERY_ON_PRODUCT_VIEW => ['type' => 'boolean', 'value' => true],
                static::GUEST_QUICK_ORDER_FORM => ['type' => 'boolean', 'value' => false],
                static::MATRIX_FORM_ON_PRODUCT_VIEW => [
                    'type' => 'string',
                    'value' => static::MATRIX_FORM_INLINE
                ],
                static::MATRIX_FORM_ON_PRODUCT_LISTING => [
                    'type' => 'string',
                    'value' => static::MATRIX_FORM_INLINE
                ],
                static::MATRIX_FORM_ALLOW_TO_ADD_EMPTY => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                static::DISPLAY_SIMPLE_VARIATIONS => [
                    'type' => 'string',
                    'value' => static::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY
                ],
                static::LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                static::DISABLE_FILTERS_ON_PRODUCT_LISTING => ['type' => 'boolean', 'value' => true],
                static::FILTERS_DISPLAY_SETTINGS_STATE => [
                    'type' => 'string',
                    'value' => static::FILTERS_DISPLAY_SETTINGS_STATE_COLLAPSED
                ],
                static::ORIGINAL_FILE_NAMES_ENABLED => [
                    'type' => 'boolean',
                    'value' => false
                ],
                static::SEARCH_AUTOCOMPLETE_MAX_PRODUCTS => [
                    'type' => 'integer',
                    'value' => 4
                ],
                static::FILTERS_POSITION => [
                    'type' => 'string',
                    'value' => static::FILTERS_POSITION_TOP
                ],
                static::ALLOW_PARTIAL_PRODUCT_SEARCH => ['value' => false, 'type' => 'boolean'],
                static::PRODUCT_DATA_EXPORT_ENABLED => [
                    'type' => 'boolean',
                    'value' => false
                ],
                static::PRODUCT_PRICES_EXPORT_ENABLED => [
                    'type' => 'boolean',
                    'value' => false
                ],
                static::PRODUCT_PRICE_TIERS_ENABLED => [
                    'type' => 'boolean',
                    'value' => false
                ],
                static::MICRODATA_WITHOUT_PRICES_DISABLED => [
                    'type' => 'boolean',
                    'value' => true
                ],
                static::SCHEMA_ORG_DESCRIPTION_FIELD => [
                    'type' => 'string',
                    'value' => static::SCHEMA_ORG_DEFAULT_DESCRIPTION
                ]
            ]
        );

        $rootNode
            ->children()
                ->arrayNode(self::PRODUCT_TYPES)
                    ->scalarPrototype()
                        ->validate()
                            ->ifNotInArray(Product::getTypes())
                            ->thenInvalid('Not allowed product type %s')
                        ->end()
                    ->end()
                    ->cannotBeEmpty()
                    ->defaultValue(Product::getTypes())
                ->end()
            ->end();

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $key): string
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [static::ROOT_NODE, $key]);
    }
}
