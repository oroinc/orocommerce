<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Helper\QuoteToOrderTestTrait;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteToOrderType;

class QuoteToOrderTypeTest extends AbstractQuoteToProductTestCase
{
    use QuoteToOrderTestTrait;

    /**
     * @var QuoteToOrderType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new QuoteToOrderType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    QuoteProductToOrderType::NAME
                        => new QuoteProductToOrderType($this->getTranslator(), $this->getUnitFormatter())
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param Quote|null $quote
     * @param array $defaultData
     * @param array $submit
     * @param array $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit($quote, array $defaultData, array $submit, array $expectedData)
    {
        $form = $this->factory->create($this->type, $quote);
        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submit);
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $firstUnitOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg');
        $secondUnitOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 16, 'kg');
        $thirdUnitOffer = $this->createOffer(3, QuoteProductOffer::PRICE_TYPE_UNIT, 17, 'kg', true);
        $bundledOffer = $this->createOffer(4, QuoteProductOffer::PRICE_TYPE_BUNDLED, 1000, 'item');

        $firstUnitQuoteProduct = new QuoteProduct();
        $firstUnitQuoteProduct->addQuoteProductOffer($firstUnitOffer);

        $secondUnitQuoteProduct = new QuoteProduct();
        $secondUnitQuoteProduct->addQuoteProductOffer($secondUnitOffer)
            ->addQuoteProductOffer($thirdUnitOffer);

        $bundledQuoteProduct = new QuoteProduct();
        $bundledQuoteProduct->addQuoteProductOffer($bundledOffer);

        $unitAndBundledQuote = new Quote();
        $unitAndBundledQuote->addQuoteProduct($bundledQuoteProduct)
            ->addQuoteProduct($firstUnitQuoteProduct)
            ->addQuoteProduct($secondUnitQuoteProduct);

        return [
            'no products' => [
                'quote' => new Quote(),
                'defaultData' => [],
                'submit' => [],
                'expectedData' => [],
            ],
            'unit and bundled products' => [
                'quote' => $unitAndBundledQuote,
                'defaultData' => [
                    $secondUnitQuoteProduct,
                    $firstUnitQuoteProduct
                ],
                'submit' => [
                    [
                        QuoteProductToOrderType::FIELD_OFFER => '2',
                        QuoteProductToOrderType::FIELD_QUANTITY => '15',

                    ],
                    [
                        QuoteProductToOrderType::FIELD_OFFER => '1',
                        QuoteProductToOrderType::FIELD_QUANTITY => '11',
                    ],
                ],
                'expectedData' => [
                    [
                        QuoteProductToOrderType::FIELD_OFFER => $secondUnitOffer,
                        QuoteProductToOrderType::FIELD_QUANTITY => 15,
                    ],
                    [
                        QuoteProductToOrderType::FIELD_OFFER => $firstUnitOffer,
                        QuoteProductToOrderType::FIELD_QUANTITY => 11,
                    ]
                ],
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Quote", "stdClass" given
     */
    public function testBuildInvalidData()
    {
        $this->factory->create($this->type, new \stdClass());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteToOrderType::NAME, $this->type->getName());
    }
}
