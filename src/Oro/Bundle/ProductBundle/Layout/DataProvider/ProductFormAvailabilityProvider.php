<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class ProductFormAvailabilityProvider
{
    /** @var ConfigManager */
    private $configManager;

    /** @var ProductMatrixAvailabilityProvider */
    private $productMatrixAvailabilityProvider;

    /** @var UserAgentProvider */
    private $userAgentProvider;

    /** @var string */
    private $matrixFormConfig;

    const POPUP_PRODUCT_VIEWS = ['gallery-view'];

    public function __construct(
        ConfigManager $configManager,
        ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider,
        UserAgentProvider $userAgentProvider
    ) {
        $this->configManager = $configManager;
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
        $this->userAgentProvider = $userAgentProvider;
    }

    /**
     * @param string $matrixFormConfig
     */
    public function setMatrixFormConfig($matrixFormConfig)
    {
        $this->matrixFormConfig = $matrixFormConfig;
    }

    /**
     * @param Product $product
     * @param string $productView
     * @return bool
     */
    public function isSimpleFormAvailable(Product $product, $productView = '')
    {
        return $this->getAvailableMatrixFormType($product, $productView) === Configuration::MATRIX_FORM_NONE;
    }

    /**
     * @param Product $product
     * @param string $productView
     * @return bool
     */
    public function isInlineMatrixFormAvailable(Product $product, $productView = '')
    {
        return $this->getAvailableMatrixFormType($product, $productView) === Configuration::MATRIX_FORM_INLINE;
    }

    /**
     * @param Product $product
     * @param string $productView
     * @return bool
     */
    public function isPopupMatrixFormAvailable(Product $product, $productView = '')
    {
        return $this->getAvailableMatrixFormType($product, $productView) === Configuration::MATRIX_FORM_POPUP;
    }

    /**
     * @param Product $product
     * @param string $productView
     * @return bool
     */
    public function isMatrixFormAvailable(Product $product, $productView = '')
    {
        return in_array($this->getAvailableMatrixFormType($product, $productView), [
            Configuration::MATRIX_FORM_INLINE,
            Configuration::MATRIX_FORM_POPUP,
        ], true);
    }

    /**
     * @param Product $product
     * @param string $productView
     * @return string
     */
    public function getAvailableMatrixFormType(Product $product, $productView = "")
    {
        $config = $this->getMatrixFormOnProductListingConfig();
        if ($config === Configuration::MATRIX_FORM_NONE
            || !$this->productMatrixAvailabilityProvider->isMatrixFormAvailable($product)
        ) {
            return Configuration::MATRIX_FORM_NONE;
        }

        if ($this->userAgentProvider->getUserAgent()->isMobile() ||
            in_array($productView, self::POPUP_PRODUCT_VIEWS, true)
        ) {
            return Configuration::MATRIX_FORM_POPUP;
        }

        return $config;
    }

    /**
     * @param Product[] $products
     * @param string $productView
     * @return array
     */
    public function getAvailableMatrixFormTypes(array $products, $productView = '')
    {
        $data = [];
        foreach ($products as $product) {
            $data[$product->getId()] = $this->getAvailableMatrixFormType($product, $productView);
        }
        return $data;
    }

    /**
     * @return string
     */
    private function getMatrixFormOnProductListingConfig()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, $this->matrixFormConfig));
    }
}
