<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductNewArrivalStickerTrait;

class ProductStickersProvider
{
    use ProductNewArrivalStickerTrait;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Product $product
     *
     * @return array
     */
    public function getStickers(Product $product)
    {
        $stickers = [];
        if ($product->isNewArrival()) {
            $stickers[] = $this->getNewArrivalSticker();
        }

        return $stickers;
    }

    /**
     * @param array|Product[] $products
     *
     * @return array
     */
    public function getStickersForProducts(array $products)
    {
        $groupedStickers = [];
        foreach ($products as $product) {
            $groupedStickers[$product->getId()] = $this->getStickers($product);
        }

        return $groupedStickers;
    }

    /**
     * @return bool
     */
    public function isStickersEnabledOnView()
    {
        $configKey = Configuration::getConfigKeyByName(Configuration::PRODUCT_PROMOTION_SHOW_ON_VIEW);

        return $this->configManager->get($configKey);
    }
}
