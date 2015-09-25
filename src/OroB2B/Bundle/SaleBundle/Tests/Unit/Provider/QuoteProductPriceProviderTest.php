<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;

class QuoteProductPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_PRICE_LIST_ID = 1;
    /**
     * @var QuoteProductPriceProvider
     */
    protected $quoteProductPriceProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractPriceListRequestHandler
     */
    protected $priceListRequestHandler;

    protected function setUp()
    {
        $this->productPriceProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListRequestHandler = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler'
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceListRequestHandler->expects($this->any())
            ->method('getPriceList')->willReturn($this->setEntityId(new PriceList(), self::DEFAULT_PRICE_LIST_ID));

        $this->quoteProductPriceProvider = new QuoteProductPriceProvider(
            $this->productPriceProvider,
            $this->priceListRequestHandler
        );
    }

    protected function tearDown()
    {
        unset($this->quoteProductPriceProvider, $this->productPriceProvider, $this->priceListRequestHandler);
    }

    /**
     * @dataProvider getTierPricesDataProvider
     * @param PriceList|null $quotePriceList
     * @param QuoteProduct[] $quoteProducts
     * @param array|null $productPriceProviderArgs
     * @param int $tierPricesCount
     */
    public function testGetTierPrices($quotePriceList, $quoteProducts, $productPriceProviderArgs, $tierPricesCount)
    {
        $quote = new Quote();
        $quote->setPriceList($quotePriceList);
        foreach ($quoteProducts as $quoteProduct) {
            $quote->addQuoteProduct($quoteProduct);
        }

        if ($productPriceProviderArgs) {
            call_user_func_array(
                [
                    $this->productPriceProvider->expects($this->once())->method('getPriceByPriceListIdAndProductIds'),
                    'with'
                ],
                $productPriceProviderArgs
            )->willReturn(range(0, $tierPricesCount - 1));
        }

        $result = $this->quoteProductPriceProvider->getTierPrices($quote);

        $this->assertInternalType('array', $result);
        $this->assertCount($tierPricesCount, $result);
    }

    /**
     * @return array
     */
    public function getTierPricesDataProvider()
    {
        $quoteProduct = $this->getQuoteProduct();
        $quoteProductReplacement = $this->getQuoteProduct('replacement');
        $emptyQuoteProduct = $this->getQuoteProduct('empty');

        $quotePriceList = $this->setEntityId(new PriceList(), 2);

        $product1 = $quoteProduct->getProduct();
        $product2 = $quoteProductReplacement->getProductReplacement();

        return [
            'default price list' => [
                'quotePriceList' => null,
                'quoteProducts' => [$quoteProduct, $quoteProductReplacement, $emptyQuoteProduct],
                'productPriceProviderArgs' => [self::DEFAULT_PRICE_LIST_ID, [$product1->getId(), $product2->getId()]],
                'tierPricesCount' => 2,
            ],
            'quote price list' => [
                'quotePriceList' => $quotePriceList,
                'quoteProducts' => [$quoteProduct, $quoteProductReplacement, $emptyQuoteProduct],
                'productPriceProviderArgs' => [$quotePriceList->getId(), [$product1->getId(), $product2->getId()]],
                'tierPricesCount' => 2,
            ],
            'empty quote products list' => [
                'quotePriceList' => $quotePriceList,
                'quoteProducts' => [],
                'productPriceProviderArgs' => null,
                'tierPricesCount' => 0,
            ],
        ];
    }

    /**
     * @dataProvider getMatchedPricesDataProvider
     * @param PriceList|null $quotePriceList
     * @param QuoteProduct[] $quoteProducts
     * @param array|null $productPriceProviderArgs
     * @param int $matchedPriceCount
     */
    public function testGetMatchedPrices($quotePriceList, $quoteProducts, $productPriceProviderArgs, $matchedPriceCount)
    {
        $quote = new Quote();
        $quote->setPriceList($quotePriceList);
        foreach ($quoteProducts as $quoteProduct) {
            $quote->addQuoteProduct($quoteProduct);
        }

        if ($productPriceProviderArgs) {
            call_user_func_array(
                [
                    $this->productPriceProvider->expects($this->once())->method('getMatchedPrices'),
                    'with'
                ],
                $productPriceProviderArgs
            )->willReturn(array_fill(0, $matchedPriceCount, new Price()));
        }

        $result = $this->quoteProductPriceProvider->getMatchedPrices($quote);

        $this->assertInternalType('array', $result);
        $this->assertCount($matchedPriceCount, $result);
        if ($matchedPriceCount) {
            $this->assertArrayHasKey('value', $result[0]);
            $this->assertArrayHasKey('currency', $result[0]);
        }
    }

    /**
     * @return array
     */
    public function getMatchedPricesDataProvider()
    {
        $quoteProduct = $this->getQuoteProduct();
        $quoteProductReplacement = $this->getQuoteProduct('replacement');
        $emptyQuoteProduct = $this->getQuoteProduct('empty');

        $defaultPriceList = $this->setEntityId(new PriceList(), self::DEFAULT_PRICE_LIST_ID);
        $quotePriceList = $this->setEntityId(new PriceList(), 2);

        $product1 = $quoteProduct->getProduct();
        $product2 = $quoteProductReplacement->getProductReplacement();

        $quoteProductOffer1 = $quoteProduct->getQuoteProductOffers()->get(0);
        $quoteProductOffer2 = $quoteProduct->getQuoteProductOffers()->get(1);
        $quoteProductOffer3 = $quoteProductReplacement->getQuoteProductOffers()->get(0);

        $productsPriceCriteria = [];
        $productsPriceCriteria[] = new ProductPriceCriteria(
            $product1,
            $quoteProductOffer1->getProductUnit(),
            $quoteProductOffer1->getQuantity(),
            $quoteProductOffer1->getPrice()->getCurrency()
        );
        $productsPriceCriteria[] = new ProductPriceCriteria(
            $product1,
            $quoteProductOffer2->getProductUnit(),
            $quoteProductOffer2->getQuantity(),
            $quoteProductOffer2->getPrice()->getCurrency()
        );
        $productsPriceCriteria[] = new ProductPriceCriteria(
            $product2,
            $quoteProductOffer3->getProductUnit(),
            $quoteProductOffer3->getQuantity(),
            $quoteProductOffer3->getPrice()->getCurrency()
        );

        return [
            'default price list' => [
                'quotePriceList' => null,
                'quoteProducts' => [$quoteProduct, $quoteProductReplacement, $emptyQuoteProduct],
                'productPriceProviderArgs' => [$productsPriceCriteria, $defaultPriceList],
                'matchedPrice' => 3,
            ],
            'quote price list' => [
                'quotePriceList' => $quotePriceList,
                'quoteProducts' => [$quoteProduct, $quoteProductReplacement, $emptyQuoteProduct],
                'productPriceProviderArgs' => [$productsPriceCriteria, $quotePriceList],
                'matchedPrice' => 3,
            ],
            'empty quote products list' => [
                'quotePriceList' => $quotePriceList,
                'quoteProducts' => [],
                'productPriceProviderArgs' => null,
                'matchedPrice' => 0,
            ],
        ];
    }

    protected function getQuoteProduct($type = '')
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $price = new Price();
        $price->setCurrency('USD');

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setProductUnit($productUnit);
        $quoteProductOffer->setQuantity(1);
        $quoteProductOffer->setPrice($price);

        $quoteProductOffer2 = new QuoteProductOffer();
        $quoteProductOffer2->setQuantity(2);

        $quoteProductOffer3 = new QuoteProductOffer();
        $quoteProductOffer3->setProductUnit($productUnit);

        /** @var Product $product1 */
        $product1 = $this->setEntityId(new Product(), 1);
        /** @var Product $product2 */
        $product2 = $this->setEntityId(new Product(), 2);

        switch ($type) {
            case 'replacement':
                $quoteProduct = new QuoteProduct();
                $quoteProduct->setProduct($product1);
                $quoteProduct->setProductReplacement($product2);
                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
                break;
            case 'empty':
                $quoteProduct = new QuoteProduct();
                break;
            default:
                $quoteProduct = new QuoteProduct();
                $quoteProduct->setProduct($product1);
                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
                $quoteProduct->addQuoteProductOffer(clone($quoteProductOffer));
                $quoteProduct->addQuoteProductOffer($quoteProductOffer2);
                $quoteProduct->addQuoteProductOffer($quoteProductOffer3);
                break;
        }
        return $quoteProduct;
    }

    /**
     * @param object $entity
     * @param int $id
     * @return object
     */
    protected function setEntityId($entity, $id)
    {
        $reflectionObject = new \ReflectionObject($entity);
        $reflectionProperty = $reflectionObject->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($entity, $id);

        return $entity;
    }
}
