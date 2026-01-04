<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_product';
    public const RELATED_PRODUCTS_ENABLED = 'related_products_enabled';
    public const RELATED_PRODUCTS_BIDIRECTIONAL = 'related_products_bidirectional';
    public const MAX_NUMBER_OF_RELATED_PRODUCTS = 'max_number_of_related_products';
    public const MAX_NUMBER_OF_RELATED_PRODUCTS_COUNT = 25;
    public const UPSELL_PRODUCTS_ENABLED = 'upsell_products_enabled';
    public const MAX_NUMBER_OF_UPSELL_PRODUCTS = 'max_number_of_upsell_products';
    public const MAX_NUMBER_OF_UPSELL_PRODUCTS_COUNT = 25;
    public const RELATED_PRODUCTS_MAX_ITEMS = 'related_products_max_items';
    public const RELATED_PRODUCTS_MAX_ITEMS_COUNT = 5;
    public const RELATED_PRODUCTS_MIN_ITEMS = 'related_products_min_items';
    public const RELATED_PRODUCTS_MIN_ITEMS_COUNT = 3;
    public const RELATED_PRODUCTS_SHOW_ADD_BUTTON = 'related_products_show_add_button';
    public const RELATED_PRODUCTS_USE_SLIDER_ON_MOBILE = 'related_products_use_slider_on_mobile';
    public const UPSELL_PRODUCTS_MAX_ITEMS = 'upsell_products_max_items';
    public const UPSELL_PRODUCTS_MAX_ITEMS_COUNT = 5;
    public const UPSELL_PRODUCTS_MIN_ITEMS = 'upsell_products_min_items';
    public const UPSELL_PRODUCTS_MIN_ITEMS_COUNT = 3;
    public const UPSELL_PRODUCTS_SHOW_ADD_BUTTON = 'upsell_products_show_add_button';
    public const UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE = 'upsell_products_use_slider_on_mobile';
    public const SINGLE_UNIT_MODE = 'single_unit_mode';
    public const SINGLE_UNIT_MODE_SHOW_CODE = 'single_unit_mode_show_code';
    public const DEFAULT_UNIT = 'default_unit';
    public const PRODUCT_IMAGE_WATERMARK_FILE = 'product_image_watermark_file';
    public const PRODUCT_IMAGE_WATERMARK_SIZE = 'product_image_watermark_size';
    public const PRODUCT_IMAGE_WATERMARK_POSITION = 'product_image_watermark_position';
    public const PRODUCT_IMAGE_PLACEHOLDER = 'product_image_placeholder';
    public const FEATURED_PRODUCTS_SEGMENT_ID = 'featured_products_segment_id';
    public const ENABLE_QUICK_ORDER_FORM = 'enable_quick_order_form';
    public const GUEST_QUICK_ORDER_FORM = 'guest_quick_order_form';
    public const DIRECT_URL_PREFIX = 'product_direct_url_prefix';
    public const BRAND_DIRECT_URL_PREFIX = 'brand_direct_url_prefix';
    public const PRODUCT_COLLECTIONS_INDEXATION_CRON_SCHEDULE = 'product_collections_indexation_cron_schedule';
    public const PRODUCT_COLLECTIONS_INDEXATION_PARTIAL = 'product_collections_indexation_partial';
    public const DEFAULT_CRON_SCHEDULE = '0 * * * *';
    public const PRODUCT_PROMOTION_SHOW_ON_VIEW = 'product_promotion_show_on_product_view';
    public const PRODUCT_COLLECTION_MASS_ACTION_LIMITATION = 'product_collections_mass_action_limitation';

    public const NEW_ARRIVALS_PRODUCT_SEGMENT_ID = 'new_arrivals_products_segment_id';
    public const NEW_ARRIVALS_MAX_ITEMS = 'new_arrivals_max_items';
    public const NEW_ARRIVALS_MIN_ITEMS = 'new_arrivals_min_items';
    public const NEW_ARRIVALS_USE_SLIDER_ON_MOBILE = 'new_arrivals_use_slider_on_mobile';

    public const IMAGE_PREVIEW_ON_PRODUCT_LISTING_ENABLED = 'image_preview_on_product_listing_enabled';
    public const POPUP_GALLERY_ON_PRODUCT_VIEW = 'popup_gallery_on_product_view';
    public const MATRIX_FORM_ON_PRODUCT_VIEW = 'matrix_form_on_product_view';
    public const MATRIX_FORM_ON_PRODUCT_LISTING = 'matrix_form_on_product_listing';
    public const MATRIX_FORM_NONE = 'none';
    public const MATRIX_FORM_INLINE = 'inline';
    public const MATRIX_FORM_POPUP = 'popup';
    public const MATRIX_FORM_ALLOW_TO_ADD_EMPTY = 'matrix_form_allow_empty';
    public const DISPLAY_SIMPLE_VARIATIONS = 'display_simple_variations';
    public const DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE = 'everywhere';
    public const DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY = 'hide_completely';
    public const DISPLAY_SIMPLE_VARIATIONS_HIDE_CATALOG = 'hide_catalog';
    public const LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING = 'limit_filters_sorters_on_product_listing';
    public const DISABLE_FILTERS_ON_PRODUCT_LISTING = 'disable_filters_on_product_listing';
    public const FILTERS_DISPLAY_SETTINGS_STATE = 'filters_display_settings_state';
    public const FILTERS_DISPLAY_SETTINGS_STATE_COLLAPSED = 'collapsed';
    public const FILTERS_DISPLAY_SETTINGS_STATE_EXPANDED = 'expanded';
    public const ORIGINAL_FILE_NAMES_ENABLED = 'original_file_names_enabled';
    public const SEARCH_AUTOCOMPLETE_MAX_PRODUCTS = 'search_autocomplete_max_products';
    public const FILTERS_POSITION = 'filters_position';
    public const FILTERS_POSITION_TOP = 'top';
    public const FILTERS_POSITION_SIDEBAR = 'sidebar';
    public const ALLOW_PARTIAL_PRODUCT_SEARCH = 'allow_partial_product_search';
    public const PRODUCT_DATA_EXPORT_ENABLED = 'product_data_export_enabled';
    public const PRODUCT_PRICES_EXPORT_ENABLED = 'product_prices_export_enabled';
    public const PRODUCT_PRICE_TIERS_ENABLED = 'product_price_tiers_export_enabled';
    public const MICRODATA_WITHOUT_PRICES_DISABLED = 'microdata_without_prices_disabled';
    public const SCHEMA_ORG_DESCRIPTION_FIELD = 'schema_org_description_field';
    public const SCHEMA_ORG_DEFAULT_DESCRIPTION = 'oro_product_full_description';
    public const PRODUCT_TYPES = 'product_types';
    public const DISPLAY_PRICE_TIERS_AS = 'product_details_display_price_tiers_as'; // BB-23597
    public const DISPLAY_PRICE_TIERS_AS_DEFAULT_VALUE = 'multi-unit-table'; // BB-23597

    // Inventory filter
    public const INVENTORY_FILTER_ENABLE_FOR_GUESTS = 'inventory_filter_enable_for_guests';
    public const INVENTORY_FILTER_TYPE = 'inventory_filter_type';
    public const INVENTORY_FILTER_TYPE_SIMPLE = 'inventory-switcher';
    public const INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER =
        'inventory_filter_in_stock_statuses_for_simple_filter';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
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
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_IN_STOCK
                        ),
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_OUT_OF_STOCK
                        ),
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
                ],
                static::DISPLAY_PRICE_TIERS_AS => [ // BB-23597
                    'type' => 'string',
                    'value' => static::DISPLAY_PRICE_TIERS_AS_DEFAULT_VALUE
                ],
                static::INVENTORY_FILTER_ENABLE_FOR_GUESTS => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                static::INVENTORY_FILTER_TYPE => [
                    'type' => 'string',
                    'value' => static::INVENTORY_FILTER_TYPE_SIMPLE,
                ],
                static::INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER => [
                    'value' => [
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_IN_STOCK
                        ),
                    ],
                ],
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
