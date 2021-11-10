<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

/**
 * Provides a set of methods to check matrix form availability for configurable products
 * for product grids and product lists.
 * @see \Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListFormAvailabilityProvider
 * @see \Oro\Bundle\ProductBundle\Layout\DataProvider\ProductViewFormAvailabilityProvider
 */
class ProductFormAvailabilityProvider
{
    private const POPUP_PRODUCT_VIEW = 'gallery-view';

    private ConfigManager $configManager;
    private string $matrixFormConfigOptionName;
    private ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider;
    private UserAgentProvider $userAgentProvider;

    public function __construct(
        ConfigManager $configManager,
        string $matrixFormConfigOptionName,
        ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider,
        UserAgentProvider $userAgentProvider
    ) {
        $this->configManager = $configManager;
        $this->matrixFormConfigOptionName = $matrixFormConfigOptionName;
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
        $this->userAgentProvider = $userAgentProvider;
    }

    /**
     * @param array  $configurableProductData [configurable product id => [product unit, variant fields count], ...]
     * @param string $productView
     *
     * @return array [configurable product id => matrix form type, ...]
     */
    public function getAvailableMatrixFormTypes(array $configurableProductData, string $productView = ''): array
    {
        if (!$configurableProductData) {
            throw new \InvalidArgumentException('The configurable product data must not be empty.');
        }

        $matrixFormType = $this->configManager->get($this->matrixFormConfigOptionName);
        if (Configuration::MATRIX_FORM_NONE === $matrixFormType) {
            return [];
        }

        $result = [];
        $matrixAvailability = $this->productMatrixAvailabilityProvider
            ->getMatrixAvailabilityByConfigurableProductData($configurableProductData);
        foreach ($matrixAvailability as $configurableProductId => $isMatrixFormAvailable) {
            if ($isMatrixFormAvailable) {
                $result[$configurableProductId] = $matrixFormType;
            }
        }
        if ($result
            && (
                self::POPUP_PRODUCT_VIEW === $productView
                || $this->userAgentProvider->getUserAgent()->isMobile()
            )
        ) {
            foreach ($result as $key => $val) {
                $result[$key] = Configuration::MATRIX_FORM_POPUP;
            }
        }

        return $result;
    }
}
