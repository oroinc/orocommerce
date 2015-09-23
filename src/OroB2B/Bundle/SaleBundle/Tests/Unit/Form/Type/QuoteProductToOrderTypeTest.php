<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Validation;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Helper\QuoteToOrderTestTrait;

class QuoteProductToOrderTypeTest extends AbstractQuoteToProductTestCase
{
    use QuoteToOrderTestTrait;

    /**
     * @var QuoteProductToOrderType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new QuoteProductToOrderType($this->getTranslator(), $this->getUnitFormatter());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param QuoteProduct $input
     * @param array $choices
     * @param array $submit
     * @param array $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(QuoteProduct $input, array $choices, array $submit, array $expectedData)
    {
        $form = $this->factory->create($this->type, $input);
        $this->assertEquals(
            $choices,
            $form->get(QuoteProductToOrderType::FIELD_OFFER)->getConfig()->getOption('choices')
        );

        $form->submit($submit);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());

        // check quote product object
        $rootView = $form->createView();
        $this->assertArrayHasKey('quote_product', $rootView->vars);
        $this->assertEquals($input, $rootView->vars['quote_product']);

        // check that offer objects passed to choices
        $offers = [];
        foreach ($input->getQuoteProductOffers() as $offer) {
            $offers[$offer->getId()] = $offer;
        }

        $offerView = $rootView->children[QuoteProductToOrderType::FIELD_OFFER];
        /** @var FormView $view */
        foreach ($offerView->children as $view) {
            $value = $view->vars['value'];
            $this->assertArrayHasKey($value, $offers);
            $this->assertArrayHasKey('offer', $view->vars);
            /** @var QuoteProductOffer $expectedOffer */
            $expectedOffer = $offers[$value];
            /** @var QuoteProductOffer $actualOffer */
            $actualOffer = $view->vars['offer'];
            $this->assertEquals($expectedOffer->getId(), $actualOffer->getId());
        }
    }

    public function submitDataProvider()
    {
        $firstUnitOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg', true);
        $secondUnitOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 16, 'kg');
        $bundledOffer = $this->createOffer(3, QuoteProductOffer::PRICE_TYPE_BUNDLED, 1000, 'item');

        $unitQuoteProduct = new QuoteProduct();
        $unitQuoteProduct->addQuoteProductOffer($firstUnitOffer)
            ->addQuoteProductOffer($secondUnitOffer);

        $mixedQuoteProduct = new QuoteProduct();
        $mixedQuoteProduct->addQuoteProductOffer($firstUnitOffer)
            ->addQuoteProductOffer($bundledOffer);

        return [
            'only unit offers' => [
                'input' => $unitQuoteProduct,
                'choices' => [
                    1 => '12 kg or more',
                    2 => '16 kg',
                ],
                'submit' => [
                    QuoteProductToOrderType::FIELD_OFFER => '2',
                    QuoteProductToOrderType::FIELD_QUANTITY => $secondUnitOffer->getQuantity(),
                ],
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_OFFER => $secondUnitOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $secondUnitOffer->getQuantity()
                ],
            ],
            'mixed offers' => [
                'input' => $mixedQuoteProduct,
                'choices' => [
                    1 => '12 kg or more',
                ],
                'submit' => [
                    QuoteProductToOrderType::FIELD_OFFER => '1',
                    QuoteProductToOrderType::FIELD_QUANTITY => '15',
                ],
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_OFFER => $firstUnitOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => 15
                ]
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "QuoteProduct", "stdClass" given
     */
    public function testBuildInvalidData()
    {
        $this->factory->create($this->type, new \stdClass());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteProductToOrderType::NAME, $this->type->getName());
    }

    /**
     * @dataProvider finishViewDataProvider
     *
     * @param string $parentViewValidation
     * @param string $quantityViewValidation
     * @param string $expectedValidation
     */
    public function testFinishView($parentViewValidation, $quantityViewValidation, $expectedValidation)
    {
        $firstUnitOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg', true);
        $secondUnitOffer = $this->createOffer(3, QuoteProductOffer::PRICE_TYPE_BUNDLED, 1000, 'item');

        $quoteProduct = new QuoteProduct();
        $quoteProduct->addQuoteProductOffer($firstUnitOffer)->addQuoteProductOffer($secondUnitOffer);

        $form = $this->factory->create($this->type, $quoteProduct);

        $formView = $form->createView();
        $formView->vars['attr']['data-validation'] = $parentViewValidation;

        $quantityView = $formView->children[QuoteProductToOrderType::FIELD_QUANTITY];
        $quantityView->vars['attr']['data-validation'] = $quantityViewValidation;

        $this->type->finishView($formView, $form, ['data' => $quoteProduct]);

        $this->assertArrayHasKey('quote_product', $formView->vars);

        /** @var FormView $offerView */
        $offerView = $formView->children[QuoteProductToOrderType::FIELD_OFFER];
        /** @var FormView $optionView */
        foreach ($offerView->children as $optionView) {
            $this->assertArrayHasKey('offer', $optionView->vars);
            $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer', $optionView->vars['offer']);
        }

        $this->assertSame($quoteProduct, $formView->vars['quote_product']);
        $this->assertEquals($expectedValidation, $quantityView->vars['attr']['data-validation']);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            [
                null,
                null,
                null
            ],
            [
                '{"param1":"value1"}',
                '{"param2":"value2"}',
                '{"param1":"value1","param2":"value2"}'
            ]
        ];
    }
}
