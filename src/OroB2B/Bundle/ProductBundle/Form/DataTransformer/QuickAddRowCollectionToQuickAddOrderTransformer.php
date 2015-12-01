<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddRowCollectionToQuickAddOrderTransformer implements DataTransformerInterface
{
    /**
     * @param QuickAddRowCollection|QuickAddRow[] $collection
     * @return array
     */
    public function transform($collection)
    {
        $result = [];

        if (null === $collection) {
            return $result;
        }

        /** @var QuickAddRow $row */
        foreach ($collection->getValidRows() as $row) {
            $result[QuickAddType::PRODUCTS_FIELD_NAME][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $row->getSku(),
                ProductDataStorage::PRODUCT_QUANTITY_KEY => $row->getQuantity()
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}
