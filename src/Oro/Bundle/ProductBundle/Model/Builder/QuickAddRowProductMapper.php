<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * The service to find a product for each row in QuickAddRowCollection.
 */
class QuickAddRowProductMapper implements QuickAddRowProductMapperInterface
{
    private QuickAddRowProductLoader $productLoader;

    public function __construct(QuickAddRowProductLoader $productLoader)
    {
        $this->productLoader = $productLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function mapProducts(QuickAddRowCollection $collection): void
    {
        $skusUppercase = [];
        $rowProductMap = [];
        /** @var QuickAddRow $row */
        foreach ($collection as $rowIndex => $row) {
            $sku = $row->getSku();
            if (!$sku) {
                continue;
            }

            $skuUppercase = mb_strtoupper($sku);
            $skusUppercase[] = $skuUppercase;
            $rowProductMap[$skuUppercase][] = $rowIndex;
        }
        if (!$skusUppercase) {
            return;
        }

        $skusUppercase = array_unique($skusUppercase);

        $products = $this->productLoader->loadProducts($skusUppercase);
        foreach ($products as $product) {
            $rowIndexes = $rowProductMap[mb_strtoupper($product->getSku())] ?? null;
            if (null === $rowIndexes) {
                continue;
            }

            foreach ($rowIndexes as $rowIndex) {
                /** @var QuickAddRow $row */
                $row = $collection[$rowIndex];
                if (null === $row->getProduct()) {
                    $row->setProduct($product);
                    if (null === $row->getUnit()) {
                        $row->setUnit($product->getPrimaryUnitPrecision()->getUnit()->getCode());
                    }
                }
            }
        }
    }
}
