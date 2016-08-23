<?php

namespace Oro\Bundle\SaleBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;

class QuoteDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, array $itemData = [])
    {
        if (!$entity instanceof Quote) {
            return;
        }

        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($product);

        $this->fillEntityData($quoteProduct, $itemData);

        if (!empty($itemData['requestProductItems'])) {
            $this->addItems($product, $quoteProduct, $itemData['requestProductItems']);
        }

        $entity->addQuoteProduct($quoteProduct);
    }

    /**
     * @param Product $product
     * @param QuoteProduct $quoteProduct
     * @param array $itemsData
     */
    protected function addItems(Product $product, QuoteProduct $quoteProduct, array $itemsData)
    {
        $defaultUnit = $this->getDefaultUnit($product);

        foreach ($itemsData as $subItemData) {
            $quoteProductRequest = new QuoteProductRequest();
            $quoteProductOffer = new QuoteProductOffer();

            $quoteProductOffer->setAllowIncrements(true);

            $this->fillEntityData($quoteProductRequest, $subItemData);
            $this->fillEntityData($quoteProductOffer, $subItemData);

            if (!$defaultUnit && !$quoteProductRequest->getProductUnit()) {
                continue;
            }

            if (!$quoteProductRequest->getProductUnit()) {
                $quoteProductRequest->setProductUnit($defaultUnit);
                $quoteProductOffer->setProductUnit($defaultUnit);
            }

            $quoteProduct->addQuoteProductRequest($quoteProductRequest);
            $quoteProduct->addQuoteProductOffer($quoteProductOffer);
        }
    }

    /**
     * @param Product $product
     * @return ProductUnit|null
     */
    protected function getDefaultUnit(Product $product)
    {
        /* @var $unitPrecision ProductUnitPrecision */
        $unitPrecision = $product->getUnitPrecisions()->first();
        if (!$unitPrecision) {
            return null;
        }

        /* @var $unit ProductUnit */
        $unit = $unitPrecision->getUnit();
        if (!$unit) {
            return null;
        }

        return $unit;
    }
}
