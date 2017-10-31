<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class ProductFormAvailabilityProvider
{
    const MATRIX_AVAILABILITY_COUNT = 2;

    /** @var ProductVariantAvailabilityProvider */
    private $variantAvailability;

    /** @var ConfigManager */
    private $configManager;

    /** @var array */
    private $cache;

    /**
     * @param ProductVariantAvailabilityProvider $variantAvailability
     * @param ConfigManager $configManager
     */
    public function __construct(
        ProductVariantAvailabilityProvider $variantAvailability,
        ConfigManager $configManager
    ) {
        $this->variantAvailability = $variantAvailability;
        $this->configManager = $configManager;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isInlineMatrixFormAvailable(Product $product)
    {
        return $this->getMatrixFormConfig() === Configuration::MATRIX_FORM_ON_PRODUCT_VIEW_INLINE
            && $this->isMatrixFormAvailable($product);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isPopupMatrixFormAvailable(Product $product)
    {
        return $this->getMatrixFormConfig() === Configuration::MATRIX_FORM_ON_PRODUCT_VIEW_POPUP
            && $this->isMatrixFormAvailable($product);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isSimpleFormAvailable(Product $product)
    {
        return $this->getMatrixFormConfig() === Configuration::MATRIX_FORM_ON_PRODUCT_VIEW_NONE
            || !$this->isMatrixFormAvailable($product);
    }

    /**
     * @return string
     */
    protected function getMatrixFormConfig()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_PRODUCT_VIEW));
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isMatrixFormAvailable(Product $product)
    {
        if ($product->isSimple()) {
            return false;
        }

        if (isset($this->cache[$product->getId()])) {
            return $this->cache[$product->getId()];
        }

        $availability = $this->getMatrixAvailability($product);
        $this->cache[$product->getId()] = $availability;

        return $availability;
    }

    /**
     * @param Product $product
     * @return bool
     */
    protected function getMatrixAvailability(Product $product)
    {
        $variants = $this->variantAvailability->getVariantFieldsAvailability($product);

        $variantsCount = count($variants);
        if ($variantsCount === 0 || $variantsCount > self::MATRIX_AVAILABILITY_COUNT) {
            return false;
        }

        $simpleProducts = $this->variantAvailability->getSimpleProductsByVariantFields($product);
        if (!$simpleProducts) {
            return false;
        }

        $configurableUnit = $product->getPrimaryUnitPrecision()->getUnit();

        foreach ($simpleProducts as $simpleProduct) {
            if (!$this->isProductSupportsUnit($simpleProduct, $configurableUnit)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @return bool
     */
    private function isProductSupportsUnit(Product $product, ProductUnit $unit)
    {
        $productUnits = $product->getUnitPrecisions()->map(
            function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            }
        );

        return $productUnits->contains($unit);
    }
}
