<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Helper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

trait QuoteToOrderTestTrait
{
    /**
     * @param int $id
     * @param int $priceType
     * @param float $quantity
     * @param string $unitCode
     * @param bool $isIncremented
     * @return QuoteProductOffer
     */
    protected function createOffer($id, $priceType, $quantity, $unitCode, $isIncremented = false)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $offer = new QuoteProductOffer();

        $reflection = new \ReflectionProperty(get_class($offer), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($offer, $id);

        $offer->setPriceType($priceType)
            ->setQuantity($quantity)
            ->setProductUnit($unit)
            ->setAllowIncrements($isIncremented);

        return $offer;
    }

    /**
     * @param array $offers
     * @param array $productParams
     * @param array $suggestedProductParams
     * @return QuoteProduct
     */
    protected function createQuoteProduct(array $offers, array $productParams = [], array $suggestedProductParams = [])
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        if ($productParams) {
            $product = new Product();
            $product->addUnitPrecision($this->createPrecision($productParams));
            $quoteProduct->setProduct($product);
        }

        if ($suggestedProductParams) {
            $suggestedProduct = new Product();
            $suggestedProduct->addUnitPrecision($this->createPrecision($suggestedProductParams));
            $quoteProduct->setProductReplacement($suggestedProduct);
        }

        return $quoteProduct;
    }

    /**
     * @param array $parameters
     * @return ProductUnitPrecision
     */
    protected function createPrecision(array $parameters)
    {
        $unit = new ProductUnit();
        $unit->setCode($parameters['unit']);

        $precision = new ProductUnitPrecision();
        $precision->setUnit($unit)
            ->setPrecision($parameters['precision']);

        return $precision;
    }
}
