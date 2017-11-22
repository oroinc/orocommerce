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
        return $this->getAvailableMatrixFormType($product) === Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_INLINE;
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getAvailableMatrixFormType(Product $product)
    {
        if ($this->getMatrixFormOnProductListingConfig() === Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_NONE
            || !$this->productFormAvailabilityProvider->isMatrixFormAvailable($product)
        ) {
            return Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_NONE;
        }

        if ($this->userAgentProvider->getUserAgent()->isMobile()) {
            return Configuration::MATRIX_FORM_ON_PRODUCT_LISTING_POPUP;
        }

        return $this->getMatrixFormOnProductListingConfig();
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
