<?php

namespace OroB2B\Bundle\ProductBundle\Rounding;

use OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class QuantityRoundingService extends AbstractRoundingService
{
    /** {@inheritdoc} */
    public function getRoundType()
    {
        return $this->configManager->get('orob2b_product.unit_rounding_type', self::ROUND_HALF_UP);
    }

    /** {@inheritdoc} */
    public function getPrecision()
    {
        throw new \BadMethodCallException('ProductUnit required to get a precision');
    }

    /**
     * @param float|int $quantity
     * @param Product $product
     * @param ProductUnit $unit
     * @return float|int
     * @throws InvalidRoundingTypeException
     */
    public function roundQuantity($quantity, ProductUnit $unit = null, Product $product = null)
    {
        if (!$unit) {
            return $quantity;
        }

        if ($product) {
            $productUnit = $product->getUnitPrecision($unit->getCode());
            if ($productUnit) {
                return $this->round($quantity, $productUnit->getPrecision());
            }
        }

        return $this->round($quantity, $unit->getDefaultPrecision());
    }
}
