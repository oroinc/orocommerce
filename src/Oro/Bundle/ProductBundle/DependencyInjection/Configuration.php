<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_product';
    const PRODUCT_IMAGE_WATERMARK_FILE = 'product_image_watermark_file';
    const PRODUCT_IMAGE_WATERMARK_SIZE = 'product_image_watermark_size';
    const PRODUCT_IMAGE_WATERMARK_POSITION = 'product_image_watermark_position';

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
                'default_unit' => ['value' => 'each'],
                'default_unit_precision' => ['value' => 0],
                'general_frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                self::PRODUCT_IMAGE_WATERMARK_FILE => ['value' => null],
                self::PRODUCT_IMAGE_WATERMARK_SIZE => ['value' => 100],
                self::PRODUCT_IMAGE_WATERMARK_POSITION => ['value' => 'center']
            ]
        );

        return $treeBuilder;
    }
}
