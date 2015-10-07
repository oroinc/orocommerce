<?php

namespace OroB2B\Bundle\SaleBundle\Form\Extension;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

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
        $quoteProduct
            ->setProduct($product)
        ;

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

        foreach ($itemsData as $subitemData) {

            $quoteProductRequest = new QuoteProductRequest();
            $quoteProductOffer = new QuoteProductOffer();
            
            $quoteProductOffer->setAllowIncrements(true);

            $this->fillEntityData($quoteProductRequest, $subitemData);
            $this->fillEntityData($quoteProductOffer, $subitemData);

            if (!$quoteProductRequest->getProductUnit() && !$defaultUnit) {
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
            return;
        }

        /* @var $unit ProductUnit */
        $unit = $unitPrecision->getUnit();
        if (!$unit) {
            return;
        }

        return $unit;
    }
}
