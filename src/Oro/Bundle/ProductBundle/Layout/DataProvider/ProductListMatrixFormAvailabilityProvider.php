<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class ProductListMatrixFormAvailabilityProvider
{
    /** @var ConfigManager */
    private $configManager;

    /** @var ProductFormAvailabilityProvider */
    private $productFormAvailabilityProvider;

    /** @var UserAgentProvider */
    private $userAgentProvider;

    public function __construct(
        ConfigManager $configManager,
        ProductFormAvailabilityProvider $productFormAvailabilityProvider,
        UserAgentProvider $userAgentProvider
    ) {
        $this->configManager = $configManager;
        $this->productFormAvailabilityProvider = $productFormAvailabilityProvider;
        $this->userAgentProvider = $userAgentProvider;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isInlineMatrixFormAvailable(Product $product)
    {
        if ($this->userAgentProvider->getUserAgent()->isMobile()) {
            return false;
        }

        return $this->getMatrixFormOnProductListingConfig() === Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE
            && $this->productFormAvailabilityProvider->isMatrixFormAvailable($product);
    }

    /**
     * @return string
     */
    private function getMatrixFormOnProductListingConfig()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_LISTING));
    }
}
