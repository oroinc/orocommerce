<?php

namespace Oro\Bundle\ProductBundle\Helper;

use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

trait ProductHolderTrait
{
    /**
     * @param array|\Traversable $productHolderIterator
     * @return array
     */
    protected function getProductIdsFromProductHolders($productHolderIterator): array
    {
        if (!(is_array($productHolderIterator) || $productHolderIterator instanceof \Traversable)) {
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
