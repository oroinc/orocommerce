<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class QuoteProductRequestTypeTest extends AbstractTest
{
    /**
     * @var QuoteProductRequestType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->formType = new QuoteProductRequestType($this->translator);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest',
                'intention'     => 'sale_quote_product_request',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote_product_request', $this->formType->getName());
    }

    public function testPreSubmit()
    {
        $form = $this->factory->create($this->formType, null, []);

        $this->formType->preSubmit(new FormEvent($form, null));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());
        $options = $config->getOptions();

        $this->assertEquals(false, $options['disabled']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @param QuoteProductRequest $inputData
     * @param array $expectedData
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(QuoteProductRequest $inputData = null, array $expectedData = [])
    {
        $form = $this->factory->create($this->formType);

        $unitCode = $inputData ? $inputData->getProductUnitCode() : '';

        $this->translator
            ->expects($expectedData['empty_value'] ? $this->once() : $this->never())
            ->method('trans')
            ->with($expectedData['empty_value'], [
                '{title}' => $unitCode,
            ])
            ->will($this->returnValue($expectedData['empty_value']))
        ;

        $this->formType->preSetData(new FormEvent($form, $inputData));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());

        $options = $form->get('productUnit')->getConfig()->getOptions();

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $options[$key], $key);
        }
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        $units = $this->getProductUnits(['kg2', 'item2']);

        return [
            'choices is []' => [
                'inputData'     => null,
                'expectedData'  => [
                    'choices'       => [],
                    'empty_value'   => null,
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is ProductUnit[] and unit is deleted' => [
                'inputData'     => $this->createQuoteProductRequest(1, $units, 'test2'),
                'expectedData'  => [
                    'choices'       => $units,
                    'empty_value'   => 'orob2b.sale.quoteproduct.product.removed',
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is ProductUnit[]' => [
                'inputData'     => $this->createQuoteProductRequest(1, $units, 'kg2'),
                'expectedData'  => [
                    'choices'       => $units,
                    'empty_value'   => null,
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is [] and unit is deleted' => [
                'inputData'     => $this->createQuoteProductRequest(1, [], 'test2'),
                'expectedData'  => [
                    'choices'       => [],
                    'empty_value'   => 'orob2b.sale.quoteproduct.product.removed',
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getQuoteProductRequest(1),
                'defaultData'   => $this->getQuoteProductRequest(1),
            ],
            'empty quote product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductRequest(2, 10, 'kg', $this->createPrice(20, 'EUR'))
                    ->setQuoteProduct(null),
                'defaultData'   => $this->getQuoteProductRequest(2)->setQuoteProduct(null),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 11,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(2, null, 'kg', $this->createPrice(11, 'EUR')),
                'defaultData'   => $this->getQuoteProductRequest(2),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 22,
                    'price'         => [
                        'value'     => 33,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(3, 22, null, $this->createPrice(33, 'EUR')),
                'defaultData'   => $this->getQuoteProductRequest(3),
            ],
            'empty price' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 44,
                    'productUnit'   => 'kg',
                ],
                'expectedData'  => $this->getQuoteProductRequest(2, 44, 'kg'),
                'defaultData'   => $this->getQuoteProductRequest(2),
            ],
            'empty request product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(5, 88, 'kg', $this->createPrice(99, 'EUR'))
                    ->setQuoteProduct(null),
                'defaultData'   => $this->getQuoteProductRequest(5)
                    ->setQuoteProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 11,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 22,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(5, 11, 'kg', $this->createPrice(22, 'EUR')),
                'defaultData'   => $this->getQuoteProductRequest(5),
            ],
        ];
    }

    /**
     * @param int $id
     * @param array $productUnits
     * @param string $unitCode
     * @return \PHPUnit_Framework_MockObject_MockObject|QuoteProductRequest
     */
    protected function createQuoteProductRequest($id, array $productUnits = [], $unitCode = null)
    {
        $productUnit = null;

        $product = new Product();
        foreach ($productUnits as $unit) {
            $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

            if ($unitCode && $unit->getCode() === $unitCode) {
                $productUnit = $unit;
            }
        }

        /* @var $item \PHPUnit_Framework_MockObject_MockObject|QuoteProductRequest */
        $item = $this->getMock('OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest');
        $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id))
        ;
        $item
            ->expects($this->any())
            ->method('getQuoteProduct')
            ->will($this->returnValue((new QuoteProduct())->setProduct($product)))
        ;
        $item
            ->expects($this->any())
            ->method('getProductUnit')
            ->will($this->returnValue($productUnit))
        ;
        $item
            ->expects($this->any())
            ->method('getProductUnitCode')
            ->will($this->returnValue($unitCode))
        ;

        return $item;
    }

    /**
     * @param float $value
     * @param string $currency
     * @return OptionalPrice
     */
    protected function createPrice($value, $currency)
    {
        return OptionalPrice::create($value, $currency);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    $priceType->getName()                   => $priceType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
