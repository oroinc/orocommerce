<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Provider\QuoteCheckoutLineItemDataProvider;

class QuoteCheckoutLineItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isTransformDataSupportedProvider
     * @param mixed $data
     * @param bool $result
     */
    public function testIsTransformDataSupported($data, $result)
    {
        $provider = new QuoteCheckoutLineItemDataProvider();

        $this->assertSame($result, $provider->isTransformDataSupported($data));
    }

    /**
     * @return array
     */
    public function isTransformDataSupportedProvider()
    {
        return [
            [
                'data' => [],
                'result' => true
            ],
            [
                'data' => [new QuoteProductOffer(), new QuoteProductOffer()],
                'result' => true
            ],
            [
                'data' => [new QuoteProductOffer(), new \stdClass()],
                'result' => false
            ],
            [
                'data' => new \stdClass(),
                'result' => false
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
            (new QuoteProductOffer())
                ->setQuoteProduct($quotProduct)
                ->setQuantity(10)
                ->setProductUnit($productUnit)
                ->setProductUnitCode('code')
                ->setPrice($price)
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
        $this->assertSame($expected, $provider->getData($submittedData));
    }
}
