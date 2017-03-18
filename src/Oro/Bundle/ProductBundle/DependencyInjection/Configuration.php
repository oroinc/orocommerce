<?php

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
    const SINGLE_UNIT_MODE = 'single_unit_mode';
    const SINGLE_UNIT_MODE_SHOW_CODE = 'single_unit_mode_show_code';
    const DEFAULT_UNIT = 'default_unit';
    const PRODUCT_IMAGE_WATERMARK_FILE = 'product_image_watermark_file';
    const PRODUCT_IMAGE_WATERMARK_SIZE = 'product_image_watermark_size';
    const PRODUCT_IMAGE_WATERMARK_POSITION = 'product_image_watermark_position';
    const FEATURED_PRODUCTS_SEGMENT_NAME = 'featured_products_segment_name';
    const ENABLE_QUICK_ORDER_FORM = 'enable_quick_order_form';
    const DIRECT_URL_PREFIX = 'product_direct_url_prefix';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(self::ROOT_NODE);

        SettingsBuilder::append(
            $rootNode,
            [
                'unit_rounding_type' => ['value' => RoundingServiceInterface::ROUND_HALF_UP],
                self::SINGLE_UNIT_MODE => ['value' => false, 'type' => 'boolean'],
                self::SINGLE_UNIT_MODE_SHOW_CODE => ['value' => false, 'type' => 'boolean'],
                self::DEFAULT_UNIT => ['value' => 'each'],
                'default_unit_precision' => ['value' => 0],
                'general_frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                self::PRODUCT_IMAGE_WATERMARK_FILE => ['value' => null],
                self::PRODUCT_IMAGE_WATERMARK_SIZE => ['value' => 100],
                self::PRODUCT_IMAGE_WATERMARK_POSITION => ['value' => 'center'],
                self::FEATURED_PRODUCTS_SEGMENT_NAME => ['value' => null],
                self::ENABLE_QUICK_ORDER_FORM => ['type' => 'boolean', 'value' => true],
                self::DIRECT_URL_PREFIX => ['value' => ''],
            ]
        );

        return $treeBuilder;
    }


    /**
     * @param string $key
     * @return string
     */
    public static function getConfigKeyByName($key)
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroProductExtension::ALIAS, $key]);
    }
}
