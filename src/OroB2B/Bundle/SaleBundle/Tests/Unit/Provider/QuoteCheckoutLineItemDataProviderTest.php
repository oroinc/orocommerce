<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
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
                'data' => new QuoteDemand(),
                'result' => true
            ]
        ];
    }

    /**
     * @dataProvider productDataProvider
     * @param Product|null $product
     * @param string $sku
     */
    public function testGetData($product, $sku)
    {
        $freeFormProduct = 'freeFromProduct';
        $quotProduct = (new QuoteProduct())
            ->setProduct($product)
            ->setFreeFormProduct($freeFormProduct)
            ->setProductSku($sku);
        $productUnit = (new ProductUnit());
        $price = new Price();
        $demand = new QuoteDemand();
        $productOffer = new QuoteProductOffer();
        $productOffer->setQuoteProduct($quotProduct)
            ->setQuantity(10)
            ->setProductUnit($productUnit)
            ->setProductUnitCode('code')
            ->setPrice($price)
        ;
        $demand->addDemandProduct(new QuoteProductDemand($demand, $productOffer, $productOffer->getQuantity()));

        $expected = [
            [
                'product' => $product,
                'productSku' => $sku,
                'freeFormProduct' => $product ? null : $freeFormProduct,
                'quantity' => 10,
                'productUnit' => $productUnit,
                'productUnitCode' => 'code',
                'price' => $price,
                'fromExternalSource' => true
            ]
        ];
        $provider = new QuoteCheckoutLineItemDataProvider();

        $this->assertEquals($expected, $provider->getData($demand));
    }

    /**
     * @return array
     */
    public function productDataProvider()
    {
        return [
            [
                (new Product())->setSku('TEST'),
                'TEST'
            ],
            [
                null,
                'SKU'
            ]
        ];
    }
}
