<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Model;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;

class QuoteProductOfferMatcherTest extends \PHPUnit_Framework_TestCase
{
    /** @var QuoteProductOfferMatcher */
    protected $matcher;

    protected function setUp()
    {
        $this->matcher = new QuoteProductOfferMatcher();
    }

    protected function tearDown()
    {
        unset($this->matcher);
    }

    /**
     * @dataProvider matchDataProvider
     *
     * @param $quoteProduct
     * @param $unitCode
     * @param $quantity
     * @param $expectedResult
     */
    public function testMatch($quoteProduct, $unitCode, $quantity, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->matcher->match($quoteProduct, $unitCode, $quantity));
    }

    /**
     * @return array
     */
    public function matchDataProvider()
    {
        return [
            'empty quote product' => [
                'quoteProduct' => $this->createQuoteProduct(),
                'unitCode' => 'item',
                'quantity' => '100',
                'expectedResult' => null,
            ],
            'quote product without expected unit code' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 1, true],
                        ['kg', 100, false],
                        ['liter', 1, true],
                        ['liter', 100, false]
                    ]
                ),
                'unitCode' => 'item',
                'quantity' => '100',
                'expectedResult' => null,
            ],
            'quote product without expected quantity' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 500, true],
                        ['kg', 1000, true],
                        ['liter', 10, true],
                        ['liter', 100, true]
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '100',
                'expectedResult' => null,
            ],
            'quote product with expected unit code and int quantity' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 1, true],
                        ['kg', 50, false],
                        ['kg', 60, true],
                        ['kg', 100, false],
                        ['liter', 120, false]
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '120',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with expected unit code and float quantity' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 1, true],
                        ['kg', 50, false],
                        ['kg', 100, false],
                        ['liter', 100, true],
                        ['liter', 100.5, true],
                        ['kg', '100.5', false],
                        ['kg', 101, true],
                        ['liter', 120, false]
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '100.5',
                'expectedResult' => $this->createQuoteProductOffer('kg', 100.5, false),
            ],
        ];
    }

    /**
     * @param array $offers
     * @return QuoteProduct
     */
    protected function createQuoteProduct(array $offers = [])
    {
        $quoteProduct = new QuoteProduct();

        foreach ($offers as $offer) {
            list($unitCode, $quantity, $allowIncrements) = $offer;

            $offer = $this->createQuoteProductOffer($unitCode, $quantity, $allowIncrements);

            $quoteProduct->addQuoteProductOffer($offer);
            $offer->setQuoteProduct(null);
        }

        return $quoteProduct;
    }

    /**
     * @param string $unitCode
     * @param float $quantity
     * @param bool $allowIncrements
     * @return QuoteProductOffer
     */
    protected function createQuoteProductOffer($unitCode, $quantity, $allowIncrements)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $item = new QuoteProductOffer();
        $item->setProductUnit($unit)->setQuantity($quantity)->setAllowIncrements($allowIncrements);

        return $item;
    }
}
