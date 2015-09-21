<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\DataTransformer\QuoteProductToOrderTransformer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Helper\QuoteToOrderTestTrait;

class QuoteProductToOrderTransformerTest extends \PHPUnit_Framework_TestCase
{
    use QuoteToOrderTestTrait;

    /**
     * @param mixed $value
     * @param array $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, array $expected)
    {
        $transformer = new QuoteProductToOrderTransformer(new QuoteProduct());
        $this->assertEquals($expected, $transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        $firstUnitOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg');
        $secondUnitOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 16, 'kg');

        $quoteProduct = new QuoteProduct();
        $quoteProduct->addQuoteProductOffer($firstUnitOffer)
            ->addQuoteProductOffer($secondUnitOffer);

        return [
            'null' => [
                'value' => null,
                'expected' => [
                    QuoteProductToOrderType::FIELD_OFFER => null,
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                ]
            ],
            'no offers' => [
                'value' => new QuoteProduct(),
                'expected' => [
                    QuoteProductToOrderType::FIELD_OFFER => null,
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                ]
            ],
            'has offers' => [
                'value' => $quoteProduct,
                'expected' => [
                    QuoteProductToOrderType::FIELD_OFFER => 1,
                    QuoteProductToOrderType::FIELD_QUANTITY => 12,
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
        $transformer = new QuoteProductToOrderTransformer(new QuoteProduct());
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
        $transformer = new QuoteProductToOrderTransformer($quoteProduct);
        $this->assertEquals($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $firstUnitOffer = $this->createOffer(11, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg');
        $secondUnitOffer = $this->createOffer(12, QuoteProductOffer::PRICE_TYPE_UNIT, 16, 'kg');

        $quoteProduct = new QuoteProduct();
        $quoteProduct->addQuoteProductOffer($firstUnitOffer)
            ->addQuoteProductOffer($secondUnitOffer);

        return [
            'null' => [
                'quoteProduct' => new QuoteProduct(),
                'value' => null,
                'expected' => [
                    QuoteProductToOrderType::FIELD_OFFER => null,
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                ],
            ],
            'no offer' => [
                'quoteProduct' => new QuoteProduct(),
                'value' => [
                    QuoteProductToOrderType::FIELD_OFFER => '42',
                    QuoteProductToOrderType::FIELD_QUANTITY => '42',
                ],
                'expected' => [
                    QuoteProductToOrderType::FIELD_OFFER => null,
                    QuoteProductToOrderType::FIELD_QUANTITY => '42',
                ],
            ],
            'valid offer' => [
                'quoteProduct' => $quoteProduct,
                'value' => [
                    QuoteProductToOrderType::FIELD_OFFER => '11',
                    QuoteProductToOrderType::FIELD_QUANTITY => '42',
                ],
                'expected' => [
                    QuoteProductToOrderType::FIELD_OFFER => $firstUnitOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => '42',
                ],
            ]
        ];
    }
}
