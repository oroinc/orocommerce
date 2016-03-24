<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Provider\QuoteCheckoutLineItemDataProvider;

class QuoteCheckoutLineItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isEntitySupportedProvider
     * @param object $entity
     * @param bool $result
     */
    public function testisEntitySupported($entity, $result)
    {
        $provider = new QuoteCheckoutLineItemDataProvider();

        $this->assertEquals($result, $provider->isEntitySupported($entity));
    }

    /**
     * @return array
     */
    public function isEntitySupportedProvider()
    {
        return [
            [
                'data' => new \stdClass(),
                'result' => false
            ],
            [
                'data' => new Quote(),
                'result' => true
            ]
        ];
    }

    public function testGetData()
    {
        $product = (new Product())->setSku('SKU');
        $quotProduct = (new QuoteProduct())
            ->setProduct($product)
            ->setFreeFormProduct('freeFromProduct');
        $productUnit = (new ProductUnit());
        $price = new Price();

        $submittedData = [
            [
                'quantity' => 10,
                'offer' =>
                    (new QuoteProductOffer())
                        ->setQuoteProduct($quotProduct)
                        ->setQuantity(10)
                        ->setProductUnit($productUnit)
                        ->setProductUnitCode('code')
                        ->setPrice($price)
            ]
        ];

        $expected = [
            [
                'product' => $product,
                'productSku' => 'SKU',
                'quantity' => 10,
                'productUnit' => $productUnit,
                'freeFromProduct' => 'freeFromProduct',
                'productUnitCode' => 'code',
                'price' => $price
            ]
        ];
        $provider = new QuoteCheckoutLineItemDataProvider();
        $this->assertSame($expected, $provider->getData(new Quote(), $submittedData));
    }
}
