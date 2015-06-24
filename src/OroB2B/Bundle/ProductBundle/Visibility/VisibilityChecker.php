<?php

namespace OroB2B\Bundle\ProductBundle\Visibility;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class VisibilityChecker
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isVisible(Product $product)
    {
        $visibility = $product->getVisibility() === Product::VISIBILITY_BY_CONFIG
            ? $this->configManager->get('orob2b_product.default_visibility')
            : $product->getVisibility();

        return $visibility === Product::VISIBILITY_VISIBLE;
    }
}
