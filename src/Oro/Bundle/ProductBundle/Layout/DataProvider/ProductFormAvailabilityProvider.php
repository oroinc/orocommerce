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
    /**
     * @var ProductVariantAvailabilityProvider
     */
    private $variantAvailability;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var array
     */
    private $matrixGridAvailable = [];

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
    public function isInlineMatrixAvailable(Product $product)
    {
        $inlineMatrixForm = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::INLINE_MATRIX_FORM_ON_PRODUCT_VIEW));

        return $inlineMatrixForm && $this->isMatrixAvailable($product);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isPopupMatrixAvailable(Product $product)
    {
        $inlineMatrixForm = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::INLINE_MATRIX_FORM_ON_PRODUCT_VIEW));

        return !$inlineMatrixForm && $this->isMatrixAvailable($product);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isSimpleAvailable(Product $product)
    {
        return !$this->isMatrixAvailable($product);
    }

    /**
     * @param Product $product
     * @return bool
     */
    protected function isMatrixAvailable(Product $product)
    {
        if (isset($this->matrixGridAvailable[$product->getId()])) {
            return $this->matrixGridAvailable[$product->getId()];
        }

        $availability = $this->getMatrixAvailability($product);
        $this->matrixGridAvailable[$product->getId()] = $availability;

        return $availability;
    }

    /**
     * @param Product $product
     * @return bool
     */
    protected function getMatrixAvailability(Product $product)
    {
        try {
            $variants = $this->variantAvailability->getVariantFieldsAvailability($product);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (count($variants) > 2) {
            return false;
        }

        $configurableUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $simpleProducts = $this->variantAvailability->getSimpleProductsByVariantFields($product);
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
