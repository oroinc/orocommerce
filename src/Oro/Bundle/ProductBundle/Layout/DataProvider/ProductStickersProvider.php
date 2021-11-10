<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;

/**
 * Provides information about product stickers.
 * @see \Oro\Bundle\ProductBundle\EventListener\ProductStickersFrontendDatagridListener
 */
class ProductStickersProvider
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Product|ProductView $product
     *
     * @return array [sticker data (array), ...]
     */
    public function getStickers(Product|ProductView $product): array
    {
        $stickers = [];
        $newArrival = $product instanceof ProductView
            ? $product->get('newArrival')
            : $product->isNewArrival();
        if ($newArrival) {
            $stickers[] = ['type' => 'new_arrival'];
        }

        return $stickers;
    }

    /**
     * @param ProductView[] $products
     *
     * @return array [product id => [sticker data (array), ...]. ...]
     */
    public function getStickersForProducts(array $products): array
    {
        $result = [];
        foreach ($products as $product) {
            $result[$product->getId()] = $this->getStickers($product);
        }

        return $result;
    }

    public function isStickersEnabledOnView(): bool
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::PRODUCT_PROMOTION_SHOW_ON_VIEW)
        );
    }
}
