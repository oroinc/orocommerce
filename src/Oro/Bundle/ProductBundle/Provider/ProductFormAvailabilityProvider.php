<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

/**
 * Provides a set of methods to check matrix form availability for configurable products on data grids.
 * @see \Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider
 */
class ProductFormAvailabilityProvider
{
    private const POPUP_PRODUCT_VIEW = 'gallery-view';

    /** @var ConfigManager */
    private $configManager;

    /** @var ProductMatrixAvailabilityProvider */
    private $productMatrixAvailabilityProvider;

    /** @var UserAgentProvider */
    private $userAgentProvider;

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

        $formType = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::MATRIX_FORM_ON_PRODUCT_LISTING)
        );
        if (Configuration::MATRIX_FORM_NONE === $formType) {
            return [];
        }

        $result = [];
        $matrixAvailability = $this->productMatrixAvailabilityProvider->getMatrixAvailabilityByConfigurableProductData(
            $configurableProductData
        );
        foreach ($matrixAvailability as $configurableProductId => $isMatrixFormAvailable) {
            if ($isMatrixFormAvailable) {
                $result[$configurableProductId] = $formType;
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
