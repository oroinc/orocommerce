<?php

namespace Oro\Bundle\ProductBundle\Helper;

use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

/**
 * Provides a method to get product ids from collection of product holders.
 */
trait ProductHolderTrait
{
    /**
     * @param array|\Traversable $productHolderIterator
     * @return array
     */
    protected function getProductIdsFromProductHolders($productHolderIterator): array
    {
        if (!is_iterable($productHolderIterator)) {
            return [];
        }

        $products = [];
        foreach ($productHolderIterator as $productHolder) {
            if (!$productHolder instanceof ProductHolderInterface) {
                continue;
            }

            $products[] = $productHolder->getProduct()->getId();
        }

        return $products;
    }
}
