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

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setProduct($product)
            ->addQuoteProductRequest($quoteProductRequest)
            ->addQuoteProductOffer($quoteProductOffer);

        $this->fillEntityData($quoteProduct, $itemData);

        if (array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $quoteProductOffer->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
            $quoteProductRequest->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);

        }

        $this->fillEntityData($quoteProductOffer, $itemData);
        $this->fillEntityData($quoteProductRequest, $itemData);

        if (!$quoteProductOffer->getProductUnit()) {
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $product->getUnitPrecisions()->first();
            if (!$unitPrecision) {
                return;
            }

            /** @var ProductUnit $unit */
            $unit = $unitPrecision->getUnit();
            if (!$unit) {
                return;
            }

            $quoteProductOffer->setProductUnit($unit);
            $quoteProductRequest->setProductUnit($unit);
        }

        if ($quoteProductOffer->getProductUnit()) {
            $entity->addQuoteProduct($quoteProduct);
        }
    }
}
