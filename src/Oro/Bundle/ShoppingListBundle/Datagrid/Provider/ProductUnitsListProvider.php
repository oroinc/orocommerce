<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;

/**
 * Collects from product and prepares a product units list.
 */
class ProductUnitsListProvider
{
    /** @var UnitLabelFormatterInterface */
    private $unitLabelFormatter;

    /**
     * @param UnitLabelFormatterInterface $unitLabelFormatter
     */
    public function __construct(UnitLabelFormatterInterface $unitLabelFormatter)
    {
        $this->unitLabelFormatter = $unitLabelFormatter;
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return array
     */
    public function getProductUnitsList(Product $product, ProductUnit $productUnit): array
    {
        $selectedCode = $productUnit->getCode();

        $list = [];
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            if (!$unitPrecision->isSell()) {
                continue;
            }

            $unitCode = $unitPrecision->getUnit()->getCode();

            $list[$unitCode] = [
                'label' => $this->unitLabelFormatter->format($unitCode),
                'selected' => $unitCode === $selectedCode,
                'precision' => $unitPrecision->getPrecision(),
                'disabled' => false,
            ];
        }

        if (!isset($list[$selectedCode])) {
            $list[$selectedCode] = [
                'label' => $this->unitLabelFormatter->format($selectedCode),
                'selected' => true,
                'disabled' => true,
            ];
        }

        return $list;
    }
}
