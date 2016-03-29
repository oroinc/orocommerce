<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Model\QuoteOfferConverter;
use OroB2B\Bundle\SaleBundle\Provider\QuoteCheckoutLineItemDataProvider;

class QuoteCheckoutLineItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  QuoteOfferConverter|\PHPUnit_Framework_MockObject_MockObject */
    protected $quoteOfferConverter;

    protected function setUp()
    {
        $this->quoteOfferConverter = $this->getMockBuilder('OroB2B\Bundle\SaleBundle\Model\QuoteOfferConverter')
            ->disableOriginalConstructor()
            ->getMock();
    }
    /**
     * @dataProvider isEntitySupportedProvider
     * @param object $entity
     * @param bool $result
     */
    public function testisEntitySupported($entity, $result)
    {

        $provider = new QuoteCheckoutLineItemDataProvider($this->quoteOfferConverter);

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

    public function testGetData()
    {
        $product = (new Product())->setSku('SKU');
        $quotProduct = (new QuoteProduct())
            ->setProduct($product)
            ->setFreeFormProduct('freeFromProduct');
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
        $demand->addDemandOffer(new QuoteProductDemand($demand, $productOffer, $productOffer->getQuantity()));

        $expected = [
            [
                'product' => $product,
                'productSku' => 'SKU',
                'quantity' => 10,
                'productUnit' => $productUnit,
                'productUnitCode' => 'code',
                'price' => $price
            ]
        ];
        $provider = new QuoteCheckoutLineItemDataProvider($this->quoteOfferConverter);

        $this->assertSame($expected, $provider->getData($demand));
    }
}
