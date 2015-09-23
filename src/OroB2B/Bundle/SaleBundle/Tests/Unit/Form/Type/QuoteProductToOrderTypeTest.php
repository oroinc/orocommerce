<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\CurrencyBundle\Model\Price;

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

        $this->type = new QuoteProductToOrderType(
            $this->getTranslator(),
            $this->getUnitFormatter(),
            $this->getNumberFormatter()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @param QuoteProduct $input
     * @param array $choices
     * @param array $submit
     * @param array $expectedData
     * @param bool $expectedReadOnly
     * @param bool $isValid
     * @param string $rootViewValidation
     * @param string $quantityViewValidation
     * @param string $expectedValidation
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        QuoteProduct $input,
        array $choices,
        array $submit,
        array $expectedData,
        $expectedReadOnly,
        $isValid = true,
        $rootViewValidation = null,
        $quantityViewValidation = null,
        $expectedValidation = null
    ) {
        $form = $this->factory->create($this->type, $input, ['attr' => ['data-validation' => $rootViewValidation]]);
        $this->assertEquals(
            $choices,
            $form->get(QuoteProductToOrderType::FIELD_OFFER)->getConfig()->getOption('choices')
        );

        $quantityForm = $form->get(QuoteProductToOrderType::FIELD_QUANTITY);
        $options = $quantityForm->getConfig()->getOptions();
        $options['attr']['data-validation'] = $quantityViewValidation;

        $form->add(QuoteProductToOrderType::FIELD_QUANTITY, 'number', $options);

        $form->submit($submit);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());

        $quantityField = $form->get(QuoteProductToOrderType::FIELD_QUANTITY);
        $this->assertEquals($expectedReadOnly, $quantityField->getConfig()->getOption('read_only'));
        $this->assertEquals(
            ['data-validation' => $quantityViewValidation],
            $quantityField->getConfig()->getOption('attr')
        );

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

            $this->assertViewDataAttributes($actualOffer, $view);
        }

        $quantityView = $rootView->children[QuoteProductToOrderType::FIELD_QUANTITY];
        $this->assertEquals($expectedValidation, $quantityView->vars['attr']['data-validation']);
    }

    /**
     * @param QuoteProductOffer $offer
     * @param FormView $view
     */
    protected function assertViewDataAttributes(QuoteProductOffer $offer, FormView $view)
    {
        $this->assertArrayHasKey('attr', $view->vars);
        $this->assertArrayHasKey('data-unit', $view->vars['attr']);
        $this->assertEquals($offer->getProductUnitCode(), $view->vars['attr']['data-unit']);

        $this->assertArrayHasKey('data-quantity', $view->vars['attr']);
        $this->assertEquals($offer->getQuantity(), $view->vars['attr']['data-quantity']);

        $this->assertArrayHasKey('data-allow-increment', $view->vars['attr']);
        $this->assertEquals($offer->isAllowIncrements(), $view->vars['attr']['data-allow-increment']);

        if ($offer->getPrice()) {
            $this->assertArrayHasKey('data-price', $view->vars['attr']);
            $this->assertEquals(
                $offer->getPrice()->getCurrency() . $offer->getPrice()->getValue(),
                $view->vars['attr']['data-price']
            );
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $firstUnitOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg', true);
        $secondUnitOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 16, 'kg');
        $secondUnitOffer->setPrice(Price::create(mt_rand(1, 5) / 10, 'USD'));
        $bundledOffer = $this->createOffer(3, QuoteProductOffer::PRICE_TYPE_BUNDLED, 1000, 'item');

        $unitQuoteProduct = new QuoteProduct();
        $unitQuoteProduct->addQuoteProductOffer($firstUnitOffer)
            ->addQuoteProductOffer($secondUnitOffer);

        $mixedQuoteProduct = new QuoteProduct();
        $mixedQuoteProduct
            ->addQuoteProductOffer($secondUnitOffer)
            ->addQuoteProductOffer($firstUnitOffer)
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
                    QuoteProductToOrderType::FIELD_QUANTITY => $secondUnitOffer->getQuantity(),
                ],
                'expectedReadOnly' => false,
            ],
            'mixed offers' => [
                'input' => $mixedQuoteProduct,
                'choices' => [
                    1 => '12 kg or more',
                    2 => '16 kg',
                ],
                'submit' => [
                    QuoteProductToOrderType::FIELD_OFFER => '1',
                    QuoteProductToOrderType::FIELD_QUANTITY => '15',
                ],
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_OFFER => $firstUnitOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => 15,
                ],
                'expectedReadOnly' => true,
            ],
            'empty offers' => [
                'input' => new QuoteProduct(),
                'choices' => [],
                'submit' => [],
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_OFFER => null,
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                ],
                'expectedReadOnly' => true,
                'isValid' => false,
                'rootViewValidation' => json_encode(['param1' => 'value1']),
                'quantityViewValidation' => json_encode(['param2' => 'value2']),
                'expectedValidation' => json_encode(['param1' => 'value1','param2' => 'value2']),
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
}
