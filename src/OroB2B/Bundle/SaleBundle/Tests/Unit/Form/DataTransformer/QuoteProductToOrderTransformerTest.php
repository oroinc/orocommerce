<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\DataTransformer\QuoteProductToOrderTransformer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;
use OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Helper\QuoteToOrderTestTrait;

class QuoteProductToOrderTransformerTest extends \PHPUnit_Framework_TestCase
{
    use QuoteToOrderTestTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteProductOfferMatcher
     */
    protected $matcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RoundingService
     */
    protected $roundingService;

    protected function setUp()
    {
        $this->matcher = $this->getMockBuilder('OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->matcher->expects($this->any())
            ->method('match')
            ->willReturnCallback(
                function (QuoteProduct $quoteProduct, $unitCode, $quantity) {
                    // simple emulation of original match algorithm
                    return $quoteProduct->getQuoteProductOffers()->filter(
                        function (QuoteProductOffer $offer) use ($quantity) {
                            return $offer->getQuantity() === $quantity;
                        }
                    )->first();
                }
            );

        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision) {
                    return round($value, $precision, PHP_ROUND_HALF_UP);
                }
            );
    }

    /**
     * @param mixed $value
     * @param array $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, array $expected)
    {
        $quoteProduct = $value ?: new QuoteProduct();
        $transformer = new QuoteProductToOrderTransformer($this->matcher, $this->roundingService, $quoteProduct);
        $this->assertEquals($expected, $transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        $firstUnitOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12.123, 'kg');
        $secondUnitOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 16.555, 'kg');

        $quoteProductWithRounding = $this->createQuoteProduct(
            [$firstUnitOffer, $secondUnitOffer],
            ['unit' => 'kg', 'precision' => 1]
        );
        $quoteProductWithRoundingSuggested = $this->createQuoteProduct(
            [$secondUnitOffer],
            [],
            ['unit' => 'kg', 'precision' => 1]
        );
        $quoteProductWithoutRounding = $this->createQuoteProduct(
            [$firstUnitOffer]
        );

        return [
            'null' => [
                'value' => null,
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                    QuoteProductToOrderType::FIELD_UNIT => null,
                ]
            ],
            'no offers' => [
                'value' => new QuoteProduct(),
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                    QuoteProductToOrderType::FIELD_UNIT => null,
                ]
            ],
            'has offers with rounding for main product' => [
                'value' => $quoteProductWithRounding,
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => 12.1,
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ]
            ],
            'has offers with rounding for suggested product' => [
                'value' => $quoteProductWithRoundingSuggested,
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => 16.6,
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ]
            ],
            'has offers without rounding' => [
                'value' => $quoteProductWithoutRounding,
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => 12,
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ]
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "QuoteProduct", "stdClass" given
     */
    public function testTransformInvalidValue()
    {
        $transformer = new QuoteProductToOrderTransformer($this->matcher, $this->roundingService, new QuoteProduct());
        $transformer->transform(new \stdClass());
    }

    /**
     * @param QuoteProduct $quoteProduct
     * @param mixed $value
     * @param array $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(QuoteProduct $quoteProduct, $value, array $expected)
    {
        $transformer = new QuoteProductToOrderTransformer($this->matcher, $this->roundingService, $quoteProduct);
        $this->assertEquals($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $firstUnitOffer = $this->createOffer(11, QuoteProductOffer::PRICE_TYPE_UNIT, 12.1, 'kg');
        $secondUnitOffer = $this->createOffer(12, QuoteProductOffer::PRICE_TYPE_UNIT, 16.5, 'kg');

        $quoteProduct = $this->createQuoteProduct(
            [$firstUnitOffer, $secondUnitOffer],
            ['unit' => 'kg', 'precision' => 1]
        );

        return [
            'null' => [
                'quoteProduct' => new QuoteProduct(),
                'value' => null,
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                    QuoteProductToOrderType::FIELD_OFFER => null,
                ],
            ],
            'no offer' => [
                'quoteProduct' => new QuoteProduct(),
                'value' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => '42',
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ],
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => '42',
                    QuoteProductToOrderType::FIELD_OFFER => null,
                ],
            ],
            'valid offer' => [
                'quoteProduct' => $quoteProduct,
                'value' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => '12.123',
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ],
                'expected' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => '12.1',
                    QuoteProductToOrderType::FIELD_OFFER => $firstUnitOffer,
                ],
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "stdClass" given
     */
    public function testReverseTransformInvalidValue()
    {
        $transformer = new QuoteProductToOrderTransformer($this->matcher, $this->roundingService, new QuoteProduct());
        $transformer->reverseTransform(new \stdClass());
    }
}
