<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddRowCollection extends ArrayCollection
{
    /**
     * @var Product[]
     */
    private $productsBySku = [];

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(PHP_EOL, $this->map(function (QuickAddRow $row) {
            return sprintf('%s, %s', $row->getSku(), $row->getQuantity());
        })->toArray());
    }

    /**
     * @return bool
     */
    public function hasCompleteRows()
    {
        return $this->getCompleteRows()->count() > 0;
    }

    /**
     * @return $this|QuickAddRow[]
     */
    public function getCompleteRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->isComplete();
        });
    }

    /**
     * @return $this|QuickAddRow[]
     */
    public function getValidRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->isValid();
        });
    }

    /**
     * @return $this|QuickAddRow[]
     */
    public function getInvalidRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return !$row->isValid();
        });
    }

    /**
     * @return array
     */
    public function getSkus()
    {
        $skus = [];

        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($sku = $row->getSku()) {
                $skus[] = $sku;
            }
        }

        return $skus;
    }

    /**
     * @return Product[]
     */
    public function getProductsBySku()
    {
        return $this->productsBySku;
    }

    /**
     * @param Product[] $productsBySku
     * @return $this
     */
    public function setProductsBySku(array $productsBySku)
    {
        $this->productsBySku = $productsBySku;

        return $this;
    }

    public function validate()
    {
        foreach ($this->getCompleteRows() as $row) {
            if (array_key_exists($row->getSku(), $this->productsBySku)) {
                $row->setValid(true);
            }
        }
    }

    /**
     * Prepares data for QuickAddType
     *
     * @return array
     */
    public function getFormData()
    {
        $data = [QuickAddType::PRODUCTS_FIELD_NAME => []];

        foreach ($this->getValidRows() as $row) {
            $data[QuickAddType::PRODUCTS_FIELD_NAME][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $row->getSku(),
                ProductDataStorage::PRODUCT_QUANTITY_KEY => $row->getQuantity()
            ];
        }

        return $data;

    }
}
