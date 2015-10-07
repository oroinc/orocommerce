<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
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

        $this->type = new QuoteProductToOrderType(
            $this->getMatcher(),
            $this->getRoundingService()
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
     * @param QuoteProduct $quoteProduct
     * @param array $submit
     * @param bool $isValid
     * @param array $expectedData
     * @param bool $expectedReadOnly
     * @param null|string $viewValidation
     * @param null|string $quantityViewValidation
     * @param null|string $expectedValidation
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        QuoteProduct $quoteProduct,
        array $submit,
        $isValid,
        array $expectedData,
        $expectedReadOnly,
        $viewValidation = null,
        $quantityViewValidation = null,
        $expectedValidation = null
    ) {
        $form = $this->factory->create($this->type, $quoteProduct, ['attr' => ['data-validation' => $viewValidation]]);

        // add test data validation to assert moving of constraint to another field
        $quantityForm = $form->get(QuoteProductToOrderType::FIELD_QUANTITY);
        $options = $quantityForm->getConfig()->getOptions();
        $options['attr']['data-validation'] = $quantityViewValidation;
        $form->add(QuoteProductToOrderType::FIELD_QUANTITY, 'number', $options);

        $form->submit($submit);
        $this->assertSame($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
        $this->assertSame($expectedReadOnly, $quantityForm->getConfig()->getOption('read_only'));

        $view = $form->createView();
        $this->assertArrayHasKey('quoteProduct', $view->vars);
        $this->assertEquals($quoteProduct, $view->vars['quoteProduct']);

        $quantityView = $view->children[QuoteProductToOrderType::FIELD_QUANTITY];
        $this->assertEquals($expectedValidation, $quantityView->vars['attr']['data-validation']);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $firstOffer = $this->createOffer(1, QuoteProductOffer::PRICE_TYPE_UNIT, 12, 'kg', true);
        $secondOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 16, 'kg');
        $thirdOffer = $this->createOffer(2, QuoteProductOffer::PRICE_TYPE_UNIT, 20, 'kg');

        return [
            'existing offers' => [
                'quoteProduct' => $this->createQuoteProduct([$firstOffer, $secondOffer, $thirdOffer]),
                'submit' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => $secondOffer->getQuantity(),
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ],
                'isValid' => true,
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => $secondOffer->getQuantity(),
                    QuoteProductToOrderType::FIELD_OFFER => $secondOffer,
                ],
                'expectedReadOnly' => false,
            ],
            'existing offers with readonly quantity' => [
                'quoteProduct' => $this->createQuoteProduct([$secondOffer, $thirdOffer]),
                'submit' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => $thirdOffer->getQuantity(),
                    QuoteProductToOrderType::FIELD_UNIT => 'kg',
                ],
                'isValid' => true,
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_QUANTITY => $thirdOffer->getQuantity(),
                    QuoteProductToOrderType::FIELD_OFFER => $thirdOffer,
                ],
                'expectedReadOnly' => true,
            ],
            'empty offers' => [
                'quoteProduct' => new QuoteProduct(),
                'submit' => [],
                'isValid' => false,
                'expectedData' => [
                    QuoteProductToOrderType::FIELD_OFFER => null,
                    QuoteProductToOrderType::FIELD_QUANTITY => null,
                ],
                'expectedReadOnly' => true,
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
