<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;

class QuickAddRowCollection extends ArrayCollection
{
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
     * @param Product[] $products
     * @return $this
     */
    public function mapProducts(array $products)
    {
        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            $sku = strtoupper($row->getSku());

            if (array_key_exists($sku, $products)) {
                $row->setProduct($products[$sku]);
            }
        }

        return $this;
    }

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        $products = [];

        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($product = $row->getProduct()) {
                $products[strtoupper($product->getSku())] = $product;
            }
        }

        return $products;
    }

    public function validate()
    {
        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($row->isComplete() &&
                $row->getProduct() &&
                is_numeric($row->getQuantity()) &&
                $row->getQuantity() > 0
            ) {
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
            $productRow = new ProductRow();
            $productRow->productSku = $row->getSku();
            $productRow->productQuantity = $row->getQuantity();
            $data[QuickAddType::PRODUCTS_FIELD_NAME][] = $productRow;
        }

        return $data;
    }
}
