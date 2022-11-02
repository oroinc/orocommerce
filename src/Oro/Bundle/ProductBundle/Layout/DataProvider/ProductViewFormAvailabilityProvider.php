<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

/**
 * Provides a set of methods to check matrix form availability for configurable products for product view page.
 * @see \Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListFormAvailabilityProvider
 * @see \Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider
 */
class ProductViewFormAvailabilityProvider
{
    private ProductFormAvailabilityProvider $productFormAvailabilityProvider;
    private ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider;
    private ConfigManager $configManager;
    private UserAgentProvider $userAgentProvider;

    public function __construct(
        ProductFormAvailabilityProvider $productFormAvailabilityProvider,
        ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider,
        ConfigManager $configManager,
        UserAgentProvider $userAgentProvider
    ) {
        $this->productFormAvailabilityProvider = $productFormAvailabilityProvider;
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
        $this->configManager = $configManager;
        $this->userAgentProvider = $userAgentProvider;
    }

    public function isSimpleFormAvailable(Product|ProductView $product): bool
    {
        return $this->getAvailableMatrixFormType($product) === Configuration::MATRIX_FORM_NONE;
    }

    public function isInlineMatrixFormAvailable(Product|ProductView $product): bool
    {
        return $this->getAvailableMatrixFormType($product) === Configuration::MATRIX_FORM_INLINE;
    }

    public function isPopupMatrixFormAvailable(Product|ProductView $product): bool
    {
        return $this->getAvailableMatrixFormType($product) === Configuration::MATRIX_FORM_POPUP;
    }

    public function isMatrixFormAvailable(Product|ProductView $product): bool
    {
        $matrixFormType = $this->getAvailableMatrixFormType($product);

        return
            Configuration::MATRIX_FORM_INLINE === $matrixFormType
            || Configuration::MATRIX_FORM_POPUP === $matrixFormType;
    }

    public function getAvailableMatrixFormType(Product|ProductView $product): string
    {
        if ($product instanceof ProductView) {
            return $this->getAvailableMatrixFormTypeForProductView($product);
        }

        $matrixFormType = $this->configManager->get('oro_product.matrix_form_on_product_view');
        if (Configuration::MATRIX_FORM_NONE === $matrixFormType) {
            return $matrixFormType;
        }

        if (!$this->productMatrixAvailabilityProvider->isMatrixFormAvailable($product)) {
            $matrixFormType = Configuration::MATRIX_FORM_NONE;
        } elseif ($this->userAgentProvider->getUserAgent()->isMobile()) {
            $matrixFormType = Configuration::MATRIX_FORM_POPUP;
        }

        return $matrixFormType;
    }

    private function getAvailableMatrixFormTypeForProductView(ProductView $product): string
    {
        if ($product->get('type') !== Product::TYPE_CONFIGURABLE) {
            return Configuration::MATRIX_FORM_NONE;
        }

        $productId = $product->getId();
        $matrixFormTypes = $this->productFormAvailabilityProvider->getAvailableMatrixFormTypes(
            [$productId => [$product->get('unit'), $product->get('variant_fields_count')]]
        );

        return $matrixFormTypes[$productId] ?? Configuration::MATRIX_FORM_NONE;
    }
}
